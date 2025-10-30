<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestController@store (REST API)
 *
 * Migrado desde: tests/Feature/CompanyManagement/Mutations/RequestCompanyMutationTest.php
 * GraphQL Mutation: requestCompany
 * REST Endpoint: POST /api/company-requests
 *
 * Verifica:
 * - Solicitud pública se crea correctamente (sin autenticación)
 * - Retorna CompanyRequest con requestCode, status PENDING
 * - No puede crear solicitud con email duplicado (REQUEST_ALREADY_EXISTS)
 * - Validación de campos requeridos
 * - businessDescription debe tener min 50 caracteres
 * - adminEmail debe ser email válido
 * - Genera requestCode único (formato REQ-YYYY-NNNNN)
 */
class CompanyRequestControllerStoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function public_request_creates_company_request_successfully()
    {
        // Arrange
        $input = [
            'company_name' => 'Innovación Digital SRL',
            'legal_name' => 'Innovación Digital Sociedad de Responsabilidad Limitada',
            'admin_email' => 'admin@innovaciondigital.bo',
            'business_description' => 'Empresa dedicada al desarrollo de software personalizado y consultoría tecnológica para PyMEs en Bolivia. Contamos con más de 5 años de experiencia en el mercado local.',
            'website' => 'https://innovaciondigital.bo',
            'industry_type' => 'Technology',
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

        // Verificar en BD
        $this->assertDatabaseHas('business.company_requests', [
            'company_name' => 'Innovación Digital SRL',
            'admin_email' => 'admin@innovaciondigital.bo',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function returns_company_request_with_request_code_and_status_pending()
    {
        // Arrange
        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'test@company.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
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
        // Arrange
        CompanyRequest::factory()->create([
            'admin_email' => 'duplicate@example.com',
            'status' => 'pending',
        ]);

        $input = [
            'company_name' => 'Another Company',
            'admin_email' => 'duplicate@example.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
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
        // Arrange - Falta company_name
        $input = [
            'admin_email' => 'test@example.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.',
            'industry_type' => 'Technology',
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert - Error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    /** @test */
    public function business_description_must_have_min_50_characters()
    {
        // Arrange
        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'test@company.com',
            'business_description' => 'Descripción muy corta', // 22 caracteres < 50
            'industry_type' => 'Technology',
        ];

        // Act
        $response = $this->postJson('/api/company-requests', $input);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_description']);
    }

    /** @test */
    public function admin_email_must_be_valid_email()
    {
        // Arrange
        $input = [
            'company_name' => 'Test Company',
            'admin_email' => 'invalid-email-format', // Email inválido
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
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
        // Arrange
        $input1 = [
            'company_name' => 'Company 1',
            'admin_email' => 'admin1@company.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
        ];

        $input2 = [
            'company_name' => 'Company 2',
            'admin_email' => 'admin2@company.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
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
        // Arrange - Solo campos requeridos
        $input = [
            'company_name' => 'Minimal Company',
            'admin_email' => 'minimal@company.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industry_type' => 'Technology',
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
