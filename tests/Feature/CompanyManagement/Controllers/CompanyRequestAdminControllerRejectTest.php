<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Jobs\SendCompanyRejectionEmailJob;
use App\Features\CompanyManagement\Mail\CompanyRejectionMail;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestAdminController@reject (REST API)
 *
 * Migrado desde: RejectCompanyRequestMutationTest (GraphQL)
 * Endpoint: POST /api/company-requests/{companyRequest}/reject
 *
 * Verifica:
 * - PLATFORM_ADMIN puede rechazar solicitud
 * - Marca solicitud como REJECTED
 * - Guarda razón de rechazo
 * - Retorna true
 * - Request inexistente lanza error (404)
 * - Request no PENDING lanza error (422)
 * - COMPANY_ADMIN no puede rechazar (403)
 * - USER no puede rechazar (403)
 * - Razón es obligatoria (validación 422)
 * - 📧 EMAIL TESTS: Envío de email con razón de rechazo
 * - 📧 Email llega a Mailpit con razón incluida
 * - NO crea empresa ni usuario cuando rechaza
 */
class CompanyRequestAdminControllerRejectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function platform_admin_can_reject_request()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'Insufficient business information provided';

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $reason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'success' => true,
                ],
            ]);
    }

    /** @test */
    public function marks_request_as_rejected()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'Business description is too vague';

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $reason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);

        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertNotNull($request->reviewed_at);
    }

    /** @test */
    public function saves_rejection_reason()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'The company does not meet our minimum requirements for business verification';

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $reason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));
        $this->assertEquals($reason, $response->json('data.reason'));

        // Assert
        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'rejection_reason' => $reason,
        ]);

        $request->refresh();
        $this->assertEquals($reason, $request->rejection_reason);
    }

    /** @test */
    public function returns_true_on_success()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test reason'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));
    }

    /** @test */
    public function nonexistent_request_returns_404()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $fakeId = '550e8400-e29b-41d4-a716-446655440999';

        // Act
        $response = $this->postJson("/api/company-requests/{$fakeId}/reject", [
            'reason' => 'Test reason'
        ], [
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
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test reason'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Only pending requests can be rejected'
            ]);
    }

    /** @test */
    public function company_admin_cannot_reject()
    {
        // Arrange
        $company = Company::factory()->create();
        $companyAdmin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $token = $this->generateAccessToken($companyAdmin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test reason'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function user_cannot_reject()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test reason'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function reason_is_required()
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act - Sin razón
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => ''
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Error de validación
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function unauthenticated_user_receives_401()
    {
        // Arrange
        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test reason'
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated'
            ]);
    }

    // =========================================================================
    // 📧 EMAIL TESTS - Verifican que email de rechazo SE ENVÍA y LLEGA
    // =========================================================================

    /** @test */
    public function sends_rejection_email_with_reason()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Failed Verification Co',
            'admin_email' => 'failed@verification.com',
        ]);

        $rejectionReason = 'Incomplete business documentation. Please provide tax ID and business license.';

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $rejectionReason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Assert - Job de email fue despachado a la cola 'emails'
        Queue::assertPushedOn('emails', SendCompanyRejectionEmailJob::class);

        // Assert - Job contiene datos correctos
        Queue::assertPushed(SendCompanyRejectionEmailJob::class, function ($job) use ($request, $rejectionReason) {
            return $job->request->id === $request->id
                && $job->reason === $rejectionReason;
        });

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

        // Assert - Email fue enviado
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) use ($request) {
            return $mail->hasTo($request->admin_email);
        });

        // Assert - Email contiene la razón de rechazo
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) use ($rejectionReason) {
            return $mail->reason === $rejectionReason;
        });
    }

    /** @test */
    public function rejection_email_arrives_to_mailpit()
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
            'company_name' => 'Mailpit Rejection Test',
            'admin_email' => 'rejection-test@mailpit.com',
        ]);

        $rejectionReason = 'Your company does not meet our service criteria at this time. Please reapply in 6 months with updated documentation.';

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $rejectionReason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));
        $this->assertEquals($rejectionReason, $response->json('data.reason'));

        // Procesar queue
        $this->artisan('queue:work', ['--once' => true, '--queue' => 'emails']);
        // Wait for email to arrive with a retry loop
        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages = $this->getMailpitMessages();
            if (count($messages) > 0) {
                break;
            }
            sleep(1);
        }

        // Assert - Email llegó a Mailpit
        $messages = $this->getMailpitMessages();
        $this->assertGreaterThan(0, count($messages), 'At least one email should arrive to Mailpit');

        // Buscar email específico
        $rejectionEmail = collect($messages)->first(function ($msg) {
            return str_contains($msg['To'][0]['Address'] ?? '', 'rejection-test@mailpit.com');
        });

        $this->assertNotNull($rejectionEmail, 'Rejection email should be in Mailpit');

        // Assert - Subject correcto
        $this->assertStringContainsString('Rechazada', $rejectionEmail['Subject'] ?? '');

        // Assert - Email contiene la razón de rechazo
        $emailBody = $this->getMailpitMessageBody($rejectionEmail['ID']);
        $this->assertStringContainsString($rejectionReason, $emailBody, 'Email should contain rejection reason');
    }

    /** @test */
    public function multiple_rejections_send_separate_emails()
    {
        // Arrange
        Mail::fake();
        Queue::fake();

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $requests = [
            [
                'request' => CompanyRequest::factory()->create([
                    'status' => 'pending',
                    'admin_email' => 'reject1@test.com',
                ]),
                'reason' => 'Reason one'
            ],
            [
                'request' => CompanyRequest::factory()->create([
                    'status' => 'pending',
                    'admin_email' => 'reject2@test.com',
                ]),
                'reason' => 'Reason two'
            ],
            [
                'request' => CompanyRequest::factory()->create([
                    'status' => 'pending',
                    'admin_email' => 'reject3@test.com',
                ]),
                'reason' => 'Reason three'
            ],
        ];

        // Act - Rechazar todas las solicitudes
        foreach ($requests as $data) {
            $response = $this->postJson("/api/company-requests/{$data['request']->id}/reject", [
                'reason' => $data['reason']
            ], [
                'Authorization' => "Bearer $token"
            ]);

            // Assert - Cada respuesta fue exitosa
            $response->assertStatus(200);
            $this->assertTrue($response->json('data.success'));
        }

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

        // Assert - Se enviaron 3 emails diferentes
        Mail::assertSent(CompanyRejectionMail::class, 3);

        // Assert - Cada email fue al destinatario correcto con su razón
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) {
            return $mail->hasTo('reject1@test.com') && $mail->reason === 'Reason one';
        });
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) {
            return $mail->hasTo('reject2@test.com') && $mail->reason === 'Reason two';
        });
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) {
            return $mail->hasTo('reject3@test.com') && $mail->reason === 'Reason three';
        });
    }

    /** @test */
    public function does_not_create_company_when_rejected()
    {
        // Arrange
        Mail::fake();
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Should Not Create This Company',
        ]);

        $initialCompanyCount = Company::count();

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Rejected for testing'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Assert - Company count did not increase
        $this->assertEquals($initialCompanyCount, Company::count());

        // Assert - No company created with this name
        $this->assertDatabaseMissing('business.companies', [
            'name' => 'Should Not Create This Company',
        ]);
    }

    /** @test */
    public function does_not_create_user_when_rejected()
    {
        // Arrange
        Mail::fake();
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'should-not-create@rejected.com',
        ]);

        // Verify user doesn't exist
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'should-not-create@rejected.com',
        ]);

        $initialUserCount = User::count();

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Invalid business information'
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        // Assert - User was NOT created
        $this->assertEquals($initialUserCount, User::count());
        $this->assertDatabaseMissing('auth.users', [
            'email' => 'should-not-create@rejected.com',
        ]);
    }

    /** @test */
    public function rejection_reason_can_be_long_text()
    {
        // Arrange
        Mail::fake();
        Queue::fake();
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create(['status' => 'pending']);

        $longReason = str_repeat('This is a detailed explanation of why the request was rejected. ', 50);

        // Act
        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => $longReason
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert - Respuesta fue exitosa
        $response->assertStatus(200);
        $this->assertTrue($response->json('data.success'));

        $returnedReason = $response->json('data.reason');
        // The returned reason might have whitespace trimmed, so compare without leading/trailing spaces
        $this->assertEquals(trim($longReason), trim($returnedReason));

        // Assert - Full reason was stored
        $stored = CompanyRequest::find($request->id);
        $this->assertEquals(trim($longReason), trim($stored->rejection_reason));

        // Assert - Job de email fue despachado
        Queue::assertPushedOn('emails', SendCompanyRejectionEmailJob::class);

        // Execute queued jobs to trigger mail sending
        $this->executeQueuedJobs();

        // Assert - Email was sent (with reason, regardless of length)
        Mail::assertSent(CompanyRejectionMail::class, function ($mail) {
            return !empty($mail->reason);
        });
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
