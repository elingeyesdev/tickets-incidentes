<?php

namespace Tests\Feature\CompanyManagement\Mutations;

use App\Features\CompanyManagement\Jobs\SendCompanyApprovalEmailJob;
use App\Features\CompanyManagement\Mail\CompanyApprovalMailForExistingUser;
use App\Features\CompanyManagement\Mail\CompanyApprovalMailForNewUser;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

/**
 * Test suite completo para approveCompanyRequest mutation
 *
 * Verifica:
 * - PLATFORM_ADMIN puede aprobar solicitud
 * - Crea empresa correctamente
 * - Crea usuario admin si no existe
 * - Asigna rol COMPANY_ADMIN al usuario
 * - Marca solicitud como APPROVED
 * - Request inexistente lanza error (REQUEST_NOT_FOUND)
 * - Request no PENDING lanza error (REQUEST_NOT_PENDING)
 * - Permisos: Solo PLATFORM_ADMIN
 * - Retorna Company creada con todos los campos
 * - 📧 EMAIL TESTS: Verifica envío y llegada de emails a Mailpit
 * - 📧 Usuario existente: Email de aprobación sin contraseña
 * - 📧 Usuario nuevo: Email con contraseña temporal
 * - 🔐 Contraseña temporal: Usuario puede hacer login
 */
class ApproveCompanyRequestMutationTest extends TestCase
{
    use RefreshDatabase, MakesGraphQLRequests;

    /** @test */
    public function platform_admin_can_approve_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Test Company',
            'admin_email' => 'newadmin@testcompany.com',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    companyCode
                    name
                    status
                    adminId
                    adminName
                    adminEmail
                    createdAt
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'approveCompanyRequest' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'createdAt',
                ],
            ],
        ]);

        $company = $response->json('data.approveCompanyRequest');
        $this->assertEquals('Test Company', $company['name']);
        $this->assertEquals('ACTIVE', $company['status']);
        $this->assertNotEmpty($company['companyCode']);
    }

    /** @test */
    public function creates_company_correctly()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New Company',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    name
                    companyCode
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $companyId = $response->json('data.approveCompanyRequest.id');

        $this->assertDatabaseHas('business.companies', [
            'id' => $companyId,
            'name' => 'New Company',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function creates_admin_user_if_not_exists()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'newuser@example.com',
        ]);

        // Verificar que el usuario no existe
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'newuser@example.com',
        ]);

        // Act
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    adminEmail
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - Usuario fue creado
        $this->assertDatabaseHas('auth.users', [
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function assigns_company_admin_role_to_user()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'companyadmin@example.com',
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    adminId
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $adminUserId = $response->json('data.approveCompanyRequest.adminId');
        $companyId = $response->json('data.approveCompanyRequest.id');

        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $adminUserId,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $companyId,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function marks_request_as_approved()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertNotNull($request->reviewed_at);
    }

    /** @test */
    public function nonexistent_request_throws_request_not_found_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $fakeId
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Request not found');

        $errors = $response->json('errors');
        $this->assertEquals('REQUEST_NOT_FOUND', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function non_pending_request_throws_request_not_pending_error()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'approved']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Only pending requests can be approved');

        $errors = $response->json('errors');
        $this->assertEquals('REQUEST_NOT_PENDING', $errors[0]['extensions']['code']);
    }

    /** @test */
    public function company_admin_cannot_approve()
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function user_cannot_approve()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($user)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function unauthenticated_user_receives_error()
    {
        // Arrange
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertGraphQLErrorMessage('Unauthenticated');
    }

    /** @test */
    public function returns_created_company_with_all_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    companyCode
                    name
                    legalName
                    status
                    supportEmail
                    adminId
                    adminName
                    adminEmail
                    createdAt
                    updatedAt
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert
        $response->assertJsonStructure([
            'data' => [
                'approveCompanyRequest' => [
                    'id',
                    'companyCode',
                    'name',
                    'status',
                    'adminId',
                    'adminName',
                    'adminEmail',
                    'createdAt',
                ],
            ],
        ]);

        $company = $response->json('data.approveCompanyRequest');
        $this->assertNotEmpty($company['id']);
        $this->assertNotEmpty($company['companyCode']);
        $this->assertEquals('ACTIVE', $company['status']);
    }

    /** @test */
    public function uses_existing_user_if_email_already_exists()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'existing@example.com',
        ]);

        $initialUserCount = User::count();

        // Act
        $response = $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    adminId
                    adminEmail
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - No se creó nuevo usuario
        $this->assertEquals($initialUserCount, User::count());

        // Se usó el usuario existente
        $this->assertEquals($existingUser->id, $response->json('data.approveCompanyRequest.adminId'));
    }

    // =========================================================================
    // 📧 EMAIL TESTS - Verifican que emails SE ENVÍAN y LLEGAN a Mailpit
    // =========================================================================

    /** @test */
    public function sends_approval_email_when_user_already_exists()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $existingUser = User::factory()->create([
            'email' => 'existing-admin@company.com',
            'password' => Hash::make('ExistingPassword123!'),
        ]);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Tech Solutions Inc',
            'admin_email' => 'existing-admin@company.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    name
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - Job de email fue despachado a la cola 'emails'
        Queue::assertPushedOn('emails', SendCompanyApprovalEmailJob::class);

        // Assert - Job fue despachado con los datos correctos
        Queue::assertPushed(SendCompanyApprovalEmailJob::class, function ($job) use ($existingUser) {
            return $job->adminUser->id === $existingUser->id
                && $job->company !== null
                && $job->request !== null;
        });

        // Assert - Email fue enviado al usuario correcto
        Mail::assertSent(CompanyApprovalMailForExistingUser::class, function ($mail) use ($existingUser) {
            return $mail->hasTo($existingUser->email);
        });

        // Assert - El email NO debe contener contraseña temporal (usuario ya tiene cuenta)
        Mail::assertSent(CompanyApprovalMailForExistingUser::class, function ($mail) {
            return !property_exists($mail, 'temporaryPassword');
        });
    }

    /** @test */
    public function sends_approval_email_with_temporary_password_for_new_user()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'StartupCo',
            'admin_email' => 'newadmin@startup.com',
        ]);

        // Verificar que usuario NO existe antes
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'newadmin@startup.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                    name
                    adminEmail
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Assert - Usuario fue creado
        $this->assertDatabaseHas('auth.users', [
            'email' => 'newadmin@startup.com',
        ]);

        $newUser = User::where('email', 'newadmin@startup.com')->first();

        // Assert - Usuario tiene contraseña temporal marcada
        $this->assertTrue($newUser->has_temporary_password);
        $this->assertNotNull($newUser->temporary_password_expires_at);
        $this->assertTrue($newUser->temporary_password_expires_at->isFuture());

        // Assert - Usuario tiene password_hash (NO null)
        $this->assertNotNull($newUser->password_hash);

        // Assert - Job de email fue despachado
        Queue::assertPushedOn('emails', SendCompanyApprovalEmailJob::class);

        // Assert - Email para NUEVO usuario fue enviado
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) use ($newUser) {
            return $mail->hasTo($newUser->email)
                && !empty($mail->temporaryPassword); // DEBE incluir contraseña temporal
        });
    }

    /** @test */
    public function new_user_can_login_with_temporary_password()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'temp-password-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Obtener usuario creado
        $newUser = User::where('email', 'temp-password-test@company.com')->first();
        $this->assertNotNull($newUser);

        // Extraer contraseña temporal del email enviado
        $temporaryPassword = null;
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) use (&$temporaryPassword) {
            $temporaryPassword = $mail->temporaryPassword;
            return true;
        });

        $this->assertNotNull($temporaryPassword, 'Temporary password should be in email');

        // Assert - Usuario PUEDE hacer login con contraseña temporal
        $loginResponse = $this->graphQL('
            mutation Login($input: LoginInput!) {
                login(input: $input) {
                    accessToken
                    user {
                        email
                        hasTemporaryPassword
                    }
                }
            }
        ', [
            'input' => [
                'email' => 'temp-password-test@company.com',
                'password' => $temporaryPassword,
            ]
        ]);

        // Assert - Login fue exitoso
        $this->assertNull($loginResponse->json('errors'));
        $this->assertNotNull($loginResponse->json('data.login.accessToken'));

        // Assert - Usuario está marcado como teniendo contraseña temporal
        $this->assertTrue($loginResponse->json('data.login.user.hasTemporaryPassword'));
    }

    /** @test */
    public function approval_email_arrives_to_mailpit_for_existing_user()
    {
        // Skip if Mailpit not available (CI/CD environments)
        if (!$this->isMailpitAvailable()) {
            $this->markTestSkipped('Mailpit is not available');
        }

        // Arrange - Limpiar Mailpit antes del test
        $this->clearMailpit();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $existingUser = User::factory()->create([
            'email' => 'mailpit-test@company.com',
        ]);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Mailpit Test Company',
            'admin_email' => 'mailpit-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Procesar queue para enviar emails
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'emails'
        ]);

        // Wait for email to arrive
        sleep(1);

        // Assert - Email llegó a Mailpit
        $messages = $this->getMailpitMessages();
        $this->assertGreaterThan(0, count($messages), 'At least one email should arrive to Mailpit');

        // Buscar email específico
        $approvalEmail = collect($messages)->first(function ($msg) {
            return str_contains($msg['To'][0]['Address'] ?? '', 'mailpit-test@company.com');
        });

        $this->assertNotNull($approvalEmail, 'Approval email should be in Mailpit');

        // Assert - Subject correcto
        $this->assertStringContainsString('Aprobada', $approvalEmail['Subject'] ?? '');

        // Assert - Email NO contiene contraseña temporal (usuario existente)
        $emailBody = $this->getMailpitMessageBody($approvalEmail['ID']);
        $this->assertStringNotContainsString('contraseña temporal', strtolower($emailBody));
        $this->assertStringNotContainsString('temporary password', strtolower($emailBody));
    }

    /** @test */
    public function approval_email_arrives_to_mailpit_for_new_user_with_temp_password()
    {
        // Skip if Mailpit not available
        if (!$this->isMailpitAvailable()) {
            $this->markTestSkipped('Mailpit is not available');
        }

        // Arrange - Limpiar Mailpit
        $this->clearMailpit();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New User Test Co',
            'admin_email' => 'newuser-mailpit@test.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        // Procesar queue
        $this->artisan('queue:work', ['--once' => true, '--queue' => 'emails']);
        sleep(1);

        // Assert - Email llegó a Mailpit
        $messages = $this->getMailpitMessages();
        $approvalEmail = collect($messages)->first(function ($msg) {
            return str_contains($msg['To'][0]['Address'] ?? '', 'newuser-mailpit@test.com');
        });

        $this->assertNotNull($approvalEmail, 'Approval email for new user should be in Mailpit');

        // Assert - Email CONTIENE contraseña temporal
        $emailBody = $this->getMailpitMessageBody($approvalEmail['ID']);
        $this->assertMatchesRegularExpression(
            '/contraseña temporal|temporary password/i',
            $emailBody,
            'Email should contain temporary password information'
        );
    }

    /** @test */
    public function temporary_password_expires_after_configured_time()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'expiry-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $this->authenticateWithJWT($admin)->graphQL('
            mutation ApproveRequest($requestId: UUID!) {
                approveCompanyRequest(requestId: $requestId) {
                    id
                }
            }
        ', [
            'requestId' => $request->id
        ]);

        $newUser = User::where('email', 'expiry-test@company.com')->first();

        // Assert - Contraseña temporal expira en 7 días (configuración por defecto)
        $expectedExpiry = now()->addDays(7);
        $actualExpiry = $newUser->temporary_password_expires_at;

        $this->assertTrue(
            $actualExpiry->between(
                $expectedExpiry->subMinute(),
                $expectedExpiry->addMinute()
            ),
            'Temporary password should expire in 7 days'
        );
    }

    /** @test */
    public function multiple_companies_can_be_approved_without_email_conflicts()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        $requests = [
            CompanyRequest::factory()->create([
                'status' => 'pending',
                'company_name' => 'Company One',
                'admin_email' => 'admin1@company1.com',
            ]),
            CompanyRequest::factory()->create([
                'status' => 'pending',
                'company_name' => 'Company Two',
                'admin_email' => 'admin2@company2.com',
            ]),
            CompanyRequest::factory()->create([
                'status' => 'pending',
                'company_name' => 'Company Three',
                'admin_email' => 'admin3@company3.com',
            ]),
        ];

        // Act - Aprobar todas las solicitudes
        foreach ($requests as $request) {
            $this->authenticateWithJWT($admin)->graphQL('
                mutation ApproveRequest($requestId: UUID!) {
                    approveCompanyRequest(requestId: $requestId) {
                        id
                    }
                }
            ', [
                'requestId' => $request->id
            ]);
        }

        // Assert - Se enviaron 3 emails diferentes
        Mail::assertSent(CompanyApprovalMailForNewUser::class, 3);

        // Assert - Cada email fue al destinatario correcto
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) {
            return $mail->hasTo('admin1@company1.com');
        });
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) {
            return $mail->hasTo('admin2@company2.com');
        });
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) {
            return $mail->hasTo('admin3@company3.com');
        });

        // Assert - 3 usuarios fueron creados
        $this->assertDatabaseHas('auth.users', ['email' => 'admin1@company1.com']);
        $this->assertDatabaseHas('auth.users', ['email' => 'admin2@company2.com']);
        $this->assertDatabaseHas('auth.users', ['email' => 'admin3@company3.com']);

        // Assert - Cada uno tiene contraseña temporal diferente
        $passwords = [];
        Mail::sent(CompanyApprovalMailForNewUser::class, function ($mail) use (&$passwords) {
            $passwords[] = $mail->temporaryPassword;
        });

        $this->assertEquals(3, count(array_unique($passwords)), 'Each user should have unique temporary password');
    }

    // =========================================================================
    // 🛠️ HELPER METHODS para Mailpit Integration
    // =========================================================================

    /**
     * Check if Mailpit is available
     */
    protected function isMailpitAvailable(): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://localhost:8025/api/v1/messages');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all messages from Mailpit
     */
    protected function clearMailpit(): void
    {
        try {
            \Illuminate\Support\Facades\Http::delete('http://localhost:8025/api/v1/messages');
        } catch (\Exception $e) {
            // Silently fail if Mailpit not available
        }
    }

    /**
     * Get all messages from Mailpit
     */
    protected function getMailpitMessages(): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get('http://localhost:8025/api/v1/messages');
            return $response->json('messages') ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get message body from Mailpit
     */
    protected function getMailpitMessageBody(string $messageId): string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get("http://localhost:8025/api/v1/message/{$messageId}");
            return $response->json('HTML') ?? $response->json('Text') ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
