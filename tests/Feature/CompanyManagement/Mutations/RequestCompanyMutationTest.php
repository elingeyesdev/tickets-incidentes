<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para requestCompany mutation
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
class RequestCompanyMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function public_request_creates_company_request_successfully()
    {
        // Arrange
        $input = [
            'companyName' => 'Innovación Digital SRL',
            'legalName' => 'Innovación Digital Sociedad de Responsabilidad Limitada',
            'adminEmail' => 'admin@innovaciondigital.bo',
            'businessDescription' => 'Empresa dedicada al desarrollo de software personalizado y consultoría tecnológica para PyMEs en Bolivia. Contamos con más de 5 años de experiencia en el mercado local.',
            'website' => 'https://innovaciondigital.bo',
            'industryType' => 'Technology',
            'estimatedUsers' => 50,
            'contactAddress' => 'Calle Comercio 456',
            'contactCity' => 'Cochabamba',
            'contactCountry' => 'Bolivia',
            'contactPostalCode' => '0000',
            'taxId' => '987654321',
        ];

        // Act - Sin autenticación (pública)
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                    requestCode
                    companyName
                    adminEmail
                    status
                    createdAt
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'requestCompany' => [
                    'id',
                    'requestCode',
                    'companyName',
                    'adminEmail',
                    'status',
                    'createdAt',
                ],
            ],
        ]);

        $request = $response->json('data.requestCompany');
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
            'companyName' => 'Test Company',
            'adminEmail' => 'test@company.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    requestCode
                    status
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $request = $response->json('data.requestCompany');
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
            'companyName' => 'Another Company',
            'adminEmail' => 'duplicate@example.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('A pending request already exists with this email');

        $errors = $response->json('errors');
        $this->assertEquals('REQUEST_ALREADY_EXISTS', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function validates_required_fields()
    {
        // Arrange - Falta companyName
        $input = [
            'adminEmail' => 'test@example.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.',
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert - Error de validación de GraphQL
        $this->assertNotEmpty($response->json('errors'));
    }

    /** @test */
    public function business_description_must_have_min_50_characters()
    {
        // Arrange
        $input = [
            'companyName' => 'Test Company',
            'adminEmail' => 'test@company.com',
            'businessDescription' => 'Descripción muy corta', // Menos de 50 caracteres
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert - Just verify there's a validation error for this field
        $response->assertGraphQLValidationKeys(['businessDescription']);
    }

    /** @test */
    public function admin_email_must_be_valid_email()
    {
        // Arrange
        $input = [
            'companyName' => 'Test Company',
            'adminEmail' => 'invalid-email-format', // Email inválido
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert - Just verify there's a validation error for this field
        $response->assertGraphQLValidationKeys(['adminEmail']);
    }

    /** @test */
    public function generates_unique_request_code()
    {
        // Arrange
        $input1 = [
            'companyName' => 'Company 1',
            'adminEmail' => 'admin1@company.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        $input2 = [
            'companyName' => 'Company 2',
            'adminEmail' => 'admin2@company.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        // Act
        $response1 = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    requestCode
                }
            }
        ', ['input' => $input1]);

        $response2 = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    requestCode
                }
            }
        ', ['input' => $input2]);

        // Assert
        $requestCode1 = $response1->json('data.requestCompany.requestCode');
        $requestCode2 = $response2->json('data.requestCompany.requestCode');

        $this->assertNotEquals($requestCode1, $requestCode2);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $requestCode1);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $requestCode2);
    }

    /** @test */
    public function optional_fields_can_be_omitted()
    {
        // Arrange - Solo campos requeridos
        $input = [
            'companyName' => 'Minimal Company',
            'adminEmail' => 'minimal@company.com',
            'businessDescription' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
            'industryType' => 'Technology',
        ];

        // Act
        $response = $this->graphQL('
            mutation RequestCompany($input: CompanyRequestInput!) {
                requestCompany(input: $input) {
                    id
                    companyName
                    adminEmail
                    status
                }
            }
        ', [
            'input' => $input
        ]);

        // Assert
        $response->assertJson([
            'data' => [
                'requestCompany' => [
                    'companyName' => 'Minimal Company',
                    'adminEmail' => 'minimal@company.com',
                    'status' => 'PENDING',
                ],
            ],
        ]);
    }
}
