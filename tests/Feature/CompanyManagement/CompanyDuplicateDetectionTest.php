<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyDuplicateDetectionService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para validar la detección de empresas duplicadas.
 *
 * EDGE CASES TESTEADOS:
 * 1. Tax ID duplicado (bloqueo absoluto)
 * 2. Admin Email + Nombre Similar (bloqueo)
 * 3. Website Domain + Nombre Similar (bloqueo)
 * 4. Nombre muy similar (advertencia)
 * 5. Permitir empresas diferentes con mismo admin_email
 */
class CompanyDuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    private CompanyDuplicateDetectionService $service;

    private User $adminUser;

    private CompanyIndustry $industry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CompanyDuplicateDetectionService;

        // Crear usuario admin de prueba
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
        ]);

        // Crear industria de prueba
        $this->industry = CompanyIndustry::factory()->create([
            'name' => 'Telecomunicaciones',
        ]);
    }

    /** @test */
    public function it_detects_duplicate_tax_id_in_existing_company(): void
    {
        // Crear empresa existente con tax_id
        Company::factory()->create([
            'name' => 'UNITEL S.A.',
            'tax_id' => '1234567890',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear solicitud con mismo tax_id
        $result = $this->service->detectDuplicates(
            companyName: 'Unitel Bolivia',
            adminEmail: 'diferente@email.com',
            taxId: '1234567890'
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertArrayHasKey('tax_id', $result['blocking_errors']);
        $this->assertStringContainsString('Ya existe una empresa', $result['blocking_errors']['tax_id']);
        $this->assertStringContainsString('1234567890', $result['blocking_errors']['tax_id']);
    }

    /** @test */
    public function it_detects_duplicate_tax_id_in_pending_request(): void
    {
        // Crear solicitud pendiente con tax_id
        CompanyRequest::factory()->create([
            'company_name' => 'VIVA',
            'tax_id' => '9876543210',
            'status' => 'pending',
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear otra solicitud con mismo tax_id
        $result = $this->service->detectDuplicates(
            companyName: 'Viva Bolivia',
            adminEmail: 'admin@viva.com',
            taxId: '9876543210'
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertArrayHasKey('tax_id', $result['blocking_errors']);
        $this->assertStringContainsString('solicitud pendiente', $result['blocking_errors']['tax_id']);
    }

    /** @test */
    public function it_allows_null_tax_id_for_multiple_companies(): void
    {
        // Crear empresa sin tax_id
        Company::factory()->create([
            'name' => 'Startup Sin NIT',
            'tax_id' => null,
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear otra empresa sin tax_id pero con nombre MUY diferente
        $result = $this->service->detectDuplicates(
            companyName: 'Empresa Totalmente Diferente XYZ ABC 123',
            adminEmail: 'otro@email.com',
            taxId: null
        );

        // NO debería bloquear por tax_id (NULL es permitido múltiples veces)
        $this->assertArrayNotHasKey('tax_id', $result['blocking_errors']);

        // Tampoco debería haber errores de bloqueo en general
        // (los nombres son muy diferentes)
        $this->assertEmpty($result['blocking_errors']);
    }

    /** @test */
    public function it_blocks_same_admin_email_with_similar_company_name(): void
    {
        // Crear empresa con admin email
        Company::factory()->create([
            'name' => 'UNITEL',
            'support_email' => 'admin@unitel.com',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear solicitud con mismo email + nombre similar
        $result = $this->service->detectDuplicates(
            companyName: 'Unitel SA', // Muy similar a "UNITEL"
            adminEmail: 'admin@unitel.com' // Mismo email
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertArrayHasKey('admin_email', $result['blocking_errors']);
        $this->assertStringContainsString('ya es el email de soporte', $result['blocking_errors']['admin_email']);
    }

    /** @test */
    public function it_allows_same_admin_email_for_different_companies(): void
    {
        // Crear empresa de telecomunicaciones
        Company::factory()->create([
            'name' => 'UNITEL',
            'support_email' => 'juan@email.com',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Crear otra industria
        $otherIndustry = CompanyIndustry::factory()->create([
            'name' => 'Comercio',
        ]);

        // Intentar crear empresa TOTALMENTE DIFERENTE con mismo admin email
        $result = $this->service->detectDuplicates(
            companyName: 'Tienda de Ropa XYZ', // Nombre completamente diferente
            adminEmail: 'juan@email.com', // Mismo email
            industryId: $otherIndustry->id
        );

        // Podría haber advertencia, pero NO bloqueo
        // (permitimos que una persona administre múltiples empresas diferentes)
        if (isset($result['warnings']['admin_email'])) {
            $this->assertStringContainsString('ADVERTENCIA', $result['warnings']['admin_email']);
        }

        // No debe bloquear si los nombres son muy diferentes
        $this->assertArrayNotHasKey('admin_email', $result['blocking_errors']);
    }

    /** @test */
    public function it_blocks_same_website_domain_with_similar_name(): void
    {
        // Crear empresa con website
        Company::factory()->create([
            'name' => 'UNITEL',
            'website' => 'https://www.unitel.com.bo',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear solicitud con mismo dominio + nombre MUY similar
        $result = $this->service->detectDuplicates(
            companyName: 'Unitel', // Exactamente el mismo nombre (solo case different)
            adminEmail: 'diferente@email.com',
            website: 'http://unitel.com.bo' // Mismo dominio (sin www)
        );

        $this->assertTrue($result['is_duplicate']);
        $this->assertArrayHasKey('website', $result['blocking_errors']);
        $this->assertStringContainsString('mismo sitio web', $result['blocking_errors']['website']);
    }

    /** @test */
    public function it_warns_about_very_similar_company_names(): void
    {
        // Crear empresa
        Company::factory()->create([
            'name' => 'Empresa Eléctrica de Bolivia',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear solicitud con nombre muy similar
        $result = $this->service->detectDuplicates(
            companyName: 'Empresa Electrica Bolivia', // Muy similar (sin tilde)
            adminEmail: 'diferente@email.com'
        );

        // Debería advertir pero NO bloquear (a menos que haya otros factores)
        if (! $result['is_duplicate']) {
            $this->assertNotEmpty($result['warnings']);
            if (isset($result['warnings']['company_name'])) {
                $this->assertStringContainsString('ADVERTENCIA', $result['warnings']['company_name']);
                $this->assertStringContainsString('nombre muy similar', $result['warnings']['company_name']);
            }
        }
    }

    /** @test */
    public function it_allows_different_companies_without_conflicts(): void
    {
        // Crear empresa
        Company::factory()->create([
            'name' => 'UNITEL',
            'tax_id' => '1111111111',
            'support_email' => 'admin@unitel.com',
            'website' => 'https://unitel.com.bo',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear empresa TOTALMENTE DIFERENTE
        $result = $this->service->detectDuplicates(
            companyName: 'VIVA',
            adminEmail: 'admin@viva.com.bo',
            taxId: '2222222222',
            website: 'https://viva.com.bo'
        );

        // NO debe detectar duplicados
        $this->assertFalse($result['is_duplicate']);
        $this->assertEmpty($result['blocking_errors']);
    }

    /** @test */
    public function it_normalizes_company_names_correctly(): void
    {
        // Crear empresa con nombre normalizado
        Company::factory()->create([
            'name' => 'UNITEL S.A.',
            'admin_user_id' => $this->adminUser->id,
            'industry_id' => $this->industry->id,
        ]);

        // Intentar crear solicitud con variaciones del mismo nombre
        $variations = [
            'unitel sa',
            'Unitel S.A.',
            'UNITEL SA',
            'U.N.I.T.E.L. S.A.',
        ];

        foreach ($variations as $variation) {
            $result = $this->service->detectDuplicates(
                companyName: $variation,
                adminEmail: 'test@email.com'
            );

            // Debería detectar similitud alta (advertencia o bloqueo dependiendo de otros factores)
            $hasWarningOrError = ! empty($result['warnings']) || ! empty($result['blocking_errors']);
            $this->assertTrue(
                $hasWarningOrError,
                "Failed to detect similarity for variation: {$variation}"
            );
        }
    }
}
