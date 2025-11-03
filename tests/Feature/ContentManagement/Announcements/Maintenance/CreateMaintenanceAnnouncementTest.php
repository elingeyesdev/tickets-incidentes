<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\Maintenance;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\ContentManagement\Jobs\PublishAnnouncementJob;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de Característica (Feature Tests) para Crear Anuncios de Mantenimiento
 *
 * Prueba el endpoint POST /api/v1/announcements/maintenance
 *
 * Cobertura:
 * - Autorización (COMPANY_ADMIN, AGENT, END_USER)
 * - Creación de borradores, publicación y programación
 * - Validación de campos (campos requeridos, enums, rangos de fechas)
 * - Inferencia del ID de la compañía desde el token JWT
 * - Despacho de trabajos (jobs) para anuncios programados
 */
class CreateMaintenanceAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function company_admin_can_create_maintenance_as_draft(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Scheduled System Maintenance',
            'content' => 'We will perform system maintenance to improve performance.',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(2)->toIso8601String(),
            'scheduled_end' => now()->addDays(2)->addHours(4)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => ['api', 'reports'],
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.title', 'Scheduled System Maintenance')
            ->assertJsonPath('data.type', 'MAINTENANCE')
            ->assertJsonMissing(['data.published_at']);

        $this->assertDatabaseHas('company_announcements', [
            'title' => 'Scheduled System Maintenance',
            'type' => 'MAINTENANCE',
            'status' => 'DRAFT',
            'author_id' => $admin->id,
        ]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_admin_can_create_and_publish_maintenance_immediately(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Urgent Maintenance Window',
            'content' => 'Emergency maintenance will be performed.',
            'urgency' => 'HIGH',
            'scheduled_start' => now()->addHours(2)->toIso8601String(),
            'scheduled_end' => now()->addHours(6)->toIso8601String(),
            'is_emergency' => true,
            'affected_services' => ['database', 'api'],
            'action' => 'publish',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'PUBLISHED')
            ->assertJsonPath('data.title', 'Urgent Maintenance Window');

        $announcement = Announcement::where('title', 'Urgent Maintenance Window')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::PUBLISHED, $announcement->status);
        $this->assertNotNull($announcement->published_at);
        $this->assertTrue($announcement->metadata['is_emergency']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_admin_can_create_and_schedule_maintenance_in_one_request(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $scheduledFor = now()->addMinutes(10);
        $payload = [
            'title' => 'Planned Database Migration',
            'content' => 'We will migrate the database to a new server.',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(3)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => ['database'],
            'action' => 'schedule',
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'SCHEDULED')
            ->assertJsonPath('data.title', 'Planned Database Migration');

        $announcement = Announcement::where('title', 'Planned Database Migration')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals(PublicationStatus::SCHEDULED, $announcement->status);
        $this->assertArrayHasKey('scheduled_for', $announcement->metadata);
        $this->assertEquals(
            $scheduledFor->format('Y-m-d H:i:s'),
            \Carbon\Carbon::parse($announcement->metadata['scheduled_for'])->format('Y-m-d H:i:s')
        );

        Queue::assertPushed(PublishAnnouncementJob::class, function ($job) use ($announcement) {
            return $job->announcement->id === $announcement->id;
        });
    }

    #[Test]
    public function validates_required_fields_for_maintenance(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $testCases = [
            'title' => ['content', 'urgency', 'scheduled_start', 'scheduled_end', 'is_emergency'],
            'content' => ['title', 'urgency', 'scheduled_start', 'scheduled_end', 'is_emergency'],
            'urgency' => ['title', 'content', 'scheduled_start', 'scheduled_end', 'is_emergency'],
            'scheduled_start' => ['title', 'content', 'urgency', 'scheduled_end', 'is_emergency'],
            'scheduled_end' => ['title', 'content', 'urgency', 'scheduled_start', 'is_emergency'],
            'is_emergency' => ['title', 'content', 'urgency', 'scheduled_start', 'scheduled_end'],
        ];

        foreach ($testCases as $missingField => $includedFields) {
            $payload = [
                'title' => 'Test Maintenance',
                'content' => 'Test content',
                'urgency' => 'MEDIUM',
                'scheduled_start' => now()->addDays(1)->toIso8601String(),
                'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
                'is_emergency' => false,
            ];

            unset($payload[$missingField]);

            // Act
            $response = $this->authenticateWithJWT($admin)
                ->postJson('/api/announcements/maintenance', $payload);

            // Assert
            $response->assertStatus(422)
                ->assertJsonValidationErrors($missingField);
        }
    }

    #[Test]
    public function validates_scheduled_end_is_after_scheduled_start(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $scheduledStart = now()->addDays(1);
        $payload = [
            'title' => 'Invalid Maintenance Window',
            'content' => 'This should fail validation.',
            'urgency' => 'LOW',
            'scheduled_start' => $scheduledStart->toIso8601String(),
            'scheduled_end' => $scheduledStart->subHour()->toIso8601String(), // Fin antes del inicio
            'is_emergency' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_end');
    }

    #[Test]
    public function validates_urgency_enum_values(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Probar valor de enum inválido
        $invalidPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'INVALID_URGENCY',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('urgency');

        // Probar urgencia CRITICAL (no permitida para mantenimiento)
        $criticalPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'CRITICAL',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $criticalPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('urgency');
    }

    #[Test]
    public function validates_affected_services_is_array(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Probar cadena en lugar de array
        $stringPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => 'not-an-array',
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $stringPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('affected_services');

        // Probar array con más de 20 elementos
        $tooManyPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => array_fill(0, 25, 'service'),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $tooManyPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('affected_services');
    }

    #[Test]
    public function validates_scheduled_for_is_at_least_5_minutes_in_future(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        // Probar fecha pasada
        $pastPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'schedule',
            'scheduled_for' => now()->subHour()->toIso8601String(),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $pastPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_for');

        // Probar 2 minutos en el futuro (menos de 5)
        $tooSoonPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'schedule',
            'scheduled_for' => now()->addMinutes(2)->toIso8601String(),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $tooSoonPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_for');

        // Probar 6 minutos en el futuro (válido)
        $validPayload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'schedule',
            'scheduled_for' => now()->addMinutes(6)->toIso8601String(),
        ];

        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $validPayload);

        $response->assertStatus(201);
    }

    #[Test]
    public function validates_scheduled_for_is_not_more_than_1_year_in_future(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(400)->toIso8601String(),
            'scheduled_end' => now()->addDays(400)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'schedule',
            'scheduled_for' => now()->addDays(400)->toIso8601String(),
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_for');
    }

    #[Test]
    public function scheduled_for_is_required_when_action_is_schedule(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'schedule',
            // Falta scheduled_for
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_for');
    }

    #[Test]
    public function scheduled_for_is_ignored_when_action_is_not_schedule(): void
    {
        // Arrange
        Queue::fake();
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Test Draft Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'action' => 'draft',
            'scheduled_for' => now()->addMinutes(10)->toIso8601String(), // Debería ser ignorado
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'DRAFT');

        $announcement = Announcement::where('title', 'Test Draft Maintenance')->first();

        $this->assertEquals(PublicationStatus::DRAFT, $announcement->status);
        $this->assertNull($announcement->published_at);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function company_id_is_inferred_from_jwt_token(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        // Get the company where this user is admin
        $adminCompany = Company::where('admin_user_id', $admin->id)->first();

        $payload = [
            'title' => 'Company Maintenance',
            'content' => 'Company-specific maintenance',
            'urgency' => 'LOW',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201);

        $announcement = Announcement::where('title', 'Company Maintenance')->first();

        $this->assertNotNull($announcement);
        $this->assertEquals($adminCompany->id, $announcement->company_id);
    }

    #[Test]
    public function author_id_is_set_to_authenticated_user(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();

        $payload = [
            'title' => 'Maintenance by Admin',
            'content' => 'Maintenance created by company admin',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('company_announcements', [
            'title' => 'Maintenance by Admin',
            'author_id' => $admin->id,
        ]);
    }

    #[Test]
    public function end_user_cannot_create_maintenance(): void
    {
        // Arrange
        $endUser = $this->createEndUser();

        $payload = [
            'title' => 'Unauthorized Maintenance',
            'content' => 'This should not be allowed',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($endUser)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('company_announcements', [
            'title' => 'Unauthorized Maintenance',
        ]);
    }

    #[Test]
    public function agent_cannot_create_maintenance(): void
    {
        // Arrange
        $agent = $this->createAgent();

        $payload = [
            'title' => 'Agent Maintenance',
            'content' => 'Agents should not be able to create maintenance',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
        ];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert
        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'Insufficient permissions']);

        $this->assertDatabaseMissing('company_announcements', [
            'title' => 'Agent Maintenance',
        ]);
    }

    // ==================== Métodos de Ayuda ====================

    /**
     * Crea un usuario final (rol USER).
     */
    private function createEndUser(): User
    {
        return User::factory()->withRole('USER')->create();
    }

    /**
     * Crea un usuario agente con el rol asignado.
     */
    private function createAgent(): User
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $user->assignRole('AGENT', $company->id);

        return $user;
    }
}
