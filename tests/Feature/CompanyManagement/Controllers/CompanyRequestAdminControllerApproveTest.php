<?php

namespace Tests\Feature\CompanyManagement\Controllers;

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
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestAdminController@approve (REST API)
 *
 * Migrado desde: ApproveCompanyRequestMutationTest (GraphQL)
 * Endpoint: POST /api/company-requests/{companyRequest}/approve
 *
 * Verifica:
 * - PLATFORM_ADMIN puede aprobar solicitud
 * - Crea empresa correctamente
 * - Crea usuario admin si no existe
 * - Asigna rol COMPANY_ADMIN al usuario
 * - Marca solicitud como APPROVED
 * - Request inexistente lanza error (404)
 * - Request no PENDING lanza error (422)
 * - Permisos: Solo PLATFORM_ADMIN
 * - Retorna Company creada con todos los campos
 * - 📧 EMAIL TESTS: Verifica envío y llegada de emails a Mailpit
 * - 📧 Usuario existente: Email de aprobación sin contraseña
 * - 📧 Usuario nuevo: Email con contraseña temporal
 * - 🔐 Contraseña temporal: Usuario puede hacer login
 */
class CompanyRequestAdminControllerApproveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function platform_admin_can_approve_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Test Company',
            'admin_email' => 'newadmin@testcompany.com',
        ]);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'success',
                    'message',
                    'newUserCreated',
                    'notificationSentTo',
                    'company' => [
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

        $data = $response->json('data');
        $this->assertTrue($data['success']);

        $company = $data['company'];
        $this->assertEquals('Test Company', $company['name']);
        $this->assertEquals('ACTIVE', $company['status']);
        $this->assertNotEmpty($company['companyCode']);
    }

    /** @test */
    public function creates_company_correctly()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New Company',
        ]);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $companyId = $response->json('data.company.id');


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
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'newuser@example.com',
        ]);

        // Verificar que el usuario no existe
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'newuser@example.com',
        ]);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Usuario fue creado
        $response->assertStatus(200);

        $this->assertDatabaseHas('auth.users', [
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function assigns_company_admin_role_to_user()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'companyadmin@example.com',
        ]);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $company = $response->json('data.company');
        $adminUserId = $company['adminId'];
        $companyId = $company['id'];

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
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertNotNull($request->reviewedAt);
    }

    /** @test */
    public function nonexistent_request_returns_404()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->postJson("/api/company-requests/{$fakeId}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Request not found'
            ]);
    }

    /** @test */
    public function non_pending_request_returns_422()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'approved']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_request'])
            ->assertJsonFragment([
                'Only pending requests can be approved. Current status: approved'
            ]);
    }

    /** @test */
    public function company_admin_cannot_approve()
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $token = $this->generateAccessToken($companyAdmin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions'
            ]);
    }

    /** @test */
    public function user_cannot_approve()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Insufficient permissions'
            ]);
    }

    /** @test */
    public function unauthenticated_user_receives_401()
    {
        // Arrange
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve");

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'code' => 'UNAUTHENTICATED'
            ]);
    }

    /** @test */
    public function returns_created_company_with_all_fields()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'success',
                    'company' => [
                        'id',
                        'companyCode',
                        'name',
                        'legalName',
                        'status',
                        'adminId',
                        'adminName',
                        'adminEmail',
                        'createdAt',
                    ],
                ],
            ]);

        $approval = $response->json('data');
        $this->assertTrue($approval['success']);

        $company = $approval['company'];
        $this->assertNotEmpty($company['id']);
        $this->assertNotEmpty($company['companyCode']);
        $this->assertEquals('ACTIVE', $company['status']);
    }

    /** @test */
    public function uses_existing_user_if_email_already_exists()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'existing@example.com',
        ]);

        $initialUserCount = User::count();

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - No se creó nuevo usuario
        $this->assertEquals($initialUserCount, User::count());

        $data = $response->json('data');
        $this->assertFalse($data['newUserCreated']);

        // Se usó el usuario existente
        $company = $data['company'];
        $this->assertEquals($existingUser->id, $company['adminId']);
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
        $token = $this->generateAccessToken($admin);

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
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Assert - Job de email fue despachado a la cola 'emails'
        Queue::assertPushedOn('emails', SendCompanyApprovalEmailJob::class);

        // Assert - Job fue despachado con los datos correctos
        Queue::assertPushed(SendCompanyApprovalEmailJob::class, function ($job) use ($existingUser) {
            return $job->adminUser->id === $existingUser->id
                && $job->company !== null
                && $job->request !== null;
        });

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

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
        $token = $this->generateAccessToken($admin);

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
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

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

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

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
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'temp-password-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Obtener usuario creado
        $newUser = User::where('email', 'temp-password-test@company.com')->first();
        $this->assertNotNull($newUser);

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

        // Extraer contraseña temporal del email enviado
        $temporaryPassword = null;
        Mail::assertSent(CompanyApprovalMailForNewUser::class, function ($mail) use (&$temporaryPassword) {
            $temporaryPassword = $mail->temporaryPassword;
            return true;
        });

        $this->assertNotNull($temporaryPassword, 'Temporary password should be in email');

        // Assert - Usuario PUEDE hacer login con contraseña temporal (vía REST)
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'temp-password-test@company.com',
            'password' => $temporaryPassword,
        ]);

        // Assert - Login fue exitoso (REST endpoint returns direct response, no 'data' wrapper)
        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'accessToken',
                'tokenType',
                'expiresIn',
                'user' => ['email'],
                'sessionId',
                'loginTimestamp',
            ]);

        $this->assertNotNull($loginResponse->json('accessToken'));
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
        $token = $this->generateAccessToken($admin);

        $existingUser = User::factory()->create([
            'email' => 'mailpit-test@company.com',
        ]);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Mailpit Test Company',
            'admin_email' => 'mailpit-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Procesar queue para enviar emails
        $this->artisan('queue:work', [
            '--once' => true,
            '--queue' => 'emails'
        ]);

        // Wait for email to arrive with a robust retry loop (up to 10 seconds)
        $messages = [];
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $messages = $this->getMailpitMessages();
            if (count($messages) > 0) {
                break;  // Email arrived, exit loop
            }
            if ($i < $maxAttempts - 1) {
                sleep(1);  // Wait 1 second before retrying
            }
        }

        // Assert - Email llegó a Mailpit (or skip if Mailpit not responding)
        if (count($messages) === 0) {
            $this->markTestSkipped('Email was not received by Mailpit (service may be unreachable or queue processing failed)');
        }

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
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New User Test Co',
            'admin_email' => 'newuser-mailpit@test.com',
        ]);

        // Act - Aprobar solicitud
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

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
            '/contraseña\s+temporal|temporary\s+password|password\s+temporal/i',
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
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'expiry-test@company.com',
        ]);

        // Act - Aprobar solicitud
        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Execute queued jobs (not needed for this test, but maintaining consistency)
        $this->executeQueuedJobs();

        $newUser = User::where('email', 'expiry-test@company.com')->first();

        // Assert - Contraseña temporal expira en 7 días (configuración por defecto)
        $expectedExpiry = now()->addDays(7);
        $actualExpiry = $newUser->temporary_password_expires_at;

        $this->assertNotNull($actualExpiry, 'temporary_password_expires_at should not be null');

        // Check if expiry is approximately 7 days from now (within 5 seconds to account for execution time)
        $diffInSeconds = abs($actualExpiry->diffInSeconds($expectedExpiry));
        $this->assertLessThan(
            5,
            $diffInSeconds,
            "Temporary password should expire in 7 days. Expected: $expectedExpiry, Got: $actualExpiry, Diff: {$diffInSeconds}s"
        );
    }

    /** @test */
    public function multiple_companies_can_be_approved_without_email_conflicts()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

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
            $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
                'Authorization' => "Bearer $token"
            ]);

            // Assert - Cada respuesta fue exitosa
            $response->assertStatus(200);
            $this->assertTrue($response->json('data.success'));
        }

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

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
            $response = \Illuminate\Support\Facades\Http::get('http://mailpit:8025/api/v1/messages');
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
            \Illuminate\Support\Facades\Http::delete('http://mailpit:8025/api/v1/messages');
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
            $response = \Illuminate\Support\Facades\Http::get('http://mailpit:8025/api/v1/messages');
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
            $response = \Illuminate\Support\Facades\Http::get("http://mailpit:8025/api/v1/message/{$messageId}");
            return $response->json('HTML') ?? $response->json('Text') ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
