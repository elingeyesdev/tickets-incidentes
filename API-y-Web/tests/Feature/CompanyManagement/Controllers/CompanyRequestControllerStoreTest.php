<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestController@store (REST API)
 *
 * ARQUITECTURA NORMALIZADA:
 * - Las solicitudes ahora crean Company con status='pending'
 * - Los detalles de la solicitud van en CompanyOnboardingDetails
 *
 * REST Endpoint: POST /api/company-requests
 *
 * Verifica:
 * - Solicitud pública se crea correctamente (sin autenticación)
 * - Retorna Company con requestCode, status PENDING
 * - No puede crear solicitud con email duplicado
 * - Validación de campos requeridos
 * - businessDescription debe tener min 50 caracteres
 * - adminEmail debe ser email válido
 * - Genera requestCode único (formato REQ-YYYY-NNNNN)
 */
class CompanyRequestControllerStoreTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    /** @test */
    public function public_request_creates_company_request_successfully()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $input = [
            'company_name' => 'Innovación Digital SRL',
            'legal_name' => 'Innovación Digital Sociedad de Responsabilidad Limitada',
            'admin_email' => 'admin@innovaciondigital.bo',
            'company_description' => 'Empresa dedicada al desarrollo de software personalizado y consultoría tecnológica para PyMEs en Bolivia. Contamos con más de 5 años de experiencia en el mercado local.',
            'request_message' => 'Necesitamos urgentemente un sistema de helpdesk profesional para atender a nuestros clientes de manera eficiente',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
            'website' => 'https://innovaciondigital.bo',
            'estimated_users' => 50,
            'contact_address' => 'Calle Comercio 456',
            'contact_city' => 'Cochabamba',
            'contact_country' => 'Bolivia',
            'contact_postal_code' => '0000',
            'tax_id' => '987654321',
        ];

        // Act - Sin autenticación (pública)
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'requestCode',
                    'companyName',
                    'adminEmail',
                    'status',
                    'createdAt',
                ],
            ]);

        $request = $response->json('data');
        $this->assertEquals('Innovación Digital SRL', $request['companyName']);
        $this->assertEquals('admin@innovaciondigital.bo', $request['adminEmail']);
        $this->assertEquals('PENDING', $request['status']);
        $this->assertNotEmpty($request['requestCode']);

        // Verificar en BD - Ahora en companies table
        $this->assertDatabaseHas('business.companies', [
            'name' => 'Innovación Digital SRL',
            'status' => 'pending',
        ]);

        // Verificar detalles de onboarding
        $this->assertDatabaseHas('business.company_onboarding_details', [
            'submitter_email' => 'admin@innovaciondigital.bo',
        ]);
    }

    /** @test */
    public function returns_company_request_with_request_code_and_status_pending()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'test@company.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'We need a professional helpdesk system to improve our customer support operations',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(201);

        $request = $response->json('data');
        $this->assertNotEmpty($request['requestCode']);
        $this->assertEquals('PENDING', $request['status']);

        // Verificar formato de requestCode (REQ-YYYY-NNNNN)
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $request['requestCode']);
    }

    /** @test */
    public function cannot_create_duplicate_request_with_same_email()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Crear empresa pendiente con el email
        $company = Company::factory()->pending()->create();
        CompanyOnboardingDetails::factory()->create([
            'company_id' => $company->id,
            'submitter_email' => 'duplicate@example.com',
        ]);

        $input = [
            'company_name' => 'Another Company',
            'admin_email' => 'duplicate@example.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'Requesting access to the helpdesk platform',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert - Laravel REST validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_email']);
    }

    /** @test */
    public function validates_required_fields()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Arrange - Falta company_name
        $input = [
            'admin_email' => 'test@example.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.',
            'request_message' => 'We need helpdesk services',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert - Error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    /** @test */
    public function company_description_must_have_min_50_characters()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'test@company.com',
            'company_description' => 'Descripción muy corta', // 22 caracteres < 50
            'request_message' => 'We need a helpdesk system for customer support',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_description']);
    }

    /** @test */
    public function admin_email_must_be_valid_email()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'invalid-email-format', // Email inválido
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'We need a helpdesk system for our company operations',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_email']);
    }

    /** @test */
    public function generates_unique_request_code()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $input1 = [
            'company_name' => 'Company 1',
            'admin_email' => 'admin1@company.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'First company requesting helpdesk system',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        $input2 = [
            'company_name' => 'Company 2',
            'admin_email' => 'admin2@company.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'Second company requesting helpdesk system',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response1 = $this->postJson('/api/company-requests', $input1);
        $response2 = $this->postJson('/api/company-requests', $input2);

        // Assert
        $requestCode1 = $response1->json('data.requestCode');
        $requestCode2 = $response2->json('data.requestCode');

        $this->assertNotEquals($requestCode1, $requestCode2);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $requestCode1);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $requestCode2);
    }

    /** @test */
    public function optional_fields_can_be_omitted()
    {
        // Arrange - Seed industries first
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        // Arrange - Solo campos requeridos
        $input = [
            'company_name' => 'Minimal Company',
            'admin_email' => 'minimal@company.com',
            'company_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'request_message' => 'Minimal request message for helpdesk access',
            'industry_id' => CompanyIndustry::where('code', 'technology')->first()->id,
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'companyName' => 'Minimal Company',
                    'adminEmail' => 'minimal@company.com',
                    'status' => 'PENDING',
                ],
            ]);
    }
}
