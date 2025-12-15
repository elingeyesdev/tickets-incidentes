<?php

namespace Tests\Feature\AuditLog;

use App\Features\AuditLog\Models\ActivityLog;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activity Log Integration Tests
 *
 * Tests para verificar que los controladores realmente
 * registran activity logs cuando ejecutan acciones.
 *
 * Estos tests verifican la integración end-to-end de:
 * - AuthController (register)
 * - PasswordResetController (password_reset_requested, password_changed)
 * - CompanyRequestAdminController (company_request_approved, company_request_rejected)
 */
class ActivityLogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $activityLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityLogService = app(ActivityLogService::class);
    }

    // ==================== AUTH CONTROLLER INTEGRATION ====================

    /** @test */
    public function register_endpoint_creates_activity_log(): void
    {
        $payload = [
            'email' => 'newuser@activitytest.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'Activity',
            'lastName' => 'Test',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201);

        // Flush buffer
        $this->activityLogService->flushBuffer();

        // Check activity log was created
        $user = User::where('email', 'newuser@activitytest.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'register',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);

        $log = ActivityLog::where('action', 'register')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('newuser@activitytest.com', $log->new_values['email']);
    }

    /** @test */
    public function login_endpoint_creates_activity_log(): void
    {
        // Create a verified user
        $user = User::factory()->verified()->withRole('USER')->create([
            'password_hash' => bcrypt('TestPassword123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'TestPassword123!',
        ]);

        $response->assertStatus(200);

        // Flush buffer
        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    /** @test */
    public function login_failed_service_method_works(): void
    {
        // Este test verifica que el método del servicio funciona
        // La integración con el AuthController se hará en una fase posterior
        $this->activityLogService->logLoginFailed('attacker@example.com', 'Invalid credentials');
        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'action' => 'login_failed',
        ]);

        $log = ActivityLog::where('action', 'login_failed')->first();
        $this->assertNotNull($log);
        $this->assertEquals('attacker@example.com', $log->metadata['email']);
        $this->assertEquals('Invalid credentials', $log->metadata['reason']);
    }

    // ==================== COMPANY REQUEST ADMIN CONTROLLER INTEGRATION ====================

    /** @test */
    public function approve_company_request_creates_activity_log(): void
    {
        // Seed industries
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Activity Log Test Company',
            'admin_email' => 'admin@activitytest.com',
        ]);

        $response = $this->postJson("/api/company-requests/{$request->id}/approve", [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200);

        // Flush buffer
        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'company_request_approved',
            'entity_type' => 'company_request',
            'entity_id' => $request->id,
        ]);

        $log = ActivityLog::where('action', 'company_request_approved')
            ->where('entity_id', $request->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Activity Log Test Company', $log->new_values['company_name']);
        $this->assertEquals('approved', $log->new_values['status']);
        $this->assertEquals('pending', $log->old_values['status']);
    }

    /** @test */
    public function reject_company_request_creates_activity_log(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $token = $this->generateAccessToken($admin);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'Rejected Company Test',
            'admin_email' => 'rejected@test.com',
        ]);

        $response = $this->postJson("/api/company-requests/{$request->id}/reject", [
            'reason' => 'Test rejection reason for activity log',
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200);

        // Flush buffer
        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'company_request_rejected',
            'entity_type' => 'company_request',
            'entity_id' => $request->id,
        ]);

        $log = ActivityLog::where('action', 'company_request_rejected')
            ->where('entity_id', $request->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Rejected Company Test', $log->new_values['company_name']);
        $this->assertEquals('rejected', $log->new_values['status']);
        $this->assertEquals('Test rejection reason for activity log', $log->new_values['reason']);
    }

    // ==================== TICKET CONTROLLER INTEGRATION ====================

    /** @test */
    public function create_ticket_creates_activity_log(): void
    {
        // Seed industries for company creation
        $this->artisan('db:seed', ['--class' => 'App\\Features\\CompanyManagement\\Database\\Seeders\\CompanyIndustrySeeder']);

        $company = \App\Features\CompanyManagement\Models\Company::factory()->create();
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Follow the company so user can create tickets
        $company->followers()->attach($user->id);

        // Create a category
        $category = \App\Features\TicketManagement\Models\Category::factory()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->postJson('/api/tickets', [
            'title' => 'Activity Log Test Ticket',
            'description' => 'This ticket tests activity log integration',
            'priority' => 'medium',
            'company_id' => $company->id,
            'category_id' => $category->id,
        ], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(201);

        // Flush buffer
        $this->activityLogService->flushBuffer();

        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'ticket_created',
            'entity_type' => 'ticket',
            'entity_id' => $ticketId,
        ]);

        $log = ActivityLog::where('action', 'ticket_created')
            ->where('entity_id', $ticketId)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Activity Log Test Ticket', $log->new_values['title']);
        $this->assertArrayHasKey('ticket_code', $log->new_values);
    }

    // ==================== DATA INTEGRITY ====================

    /** @test */
    public function activity_log_stores_ip_address(): void
    {
        $payload = [
            'email' => 'iptest@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'IP',
            'lastName' => 'Test',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $this->postJson('/api/auth/register', $payload);
        $this->activityLogService->flushBuffer();

        $log = ActivityLog::where('action', 'register')->latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->ip_address);
    }

    /** @test */
    public function activity_log_stores_user_agent(): void
    {
        $payload = [
            'email' => 'uatest@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'UA',
            'lastName' => 'Test',
            'acceptsTerms' => true,
            'acceptsPrivacyPolicy' => true,
        ];

        $this->postJson('/api/auth/register', $payload, [
            'User-Agent' => 'TestBrowser/1.0',
        ]);
        $this->activityLogService->flushBuffer();

        $log = ActivityLog::where('action', 'register')->latest()->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->user_agent);
    }

    /** @test */
    public function activity_log_timestamps_are_correct(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $beforeLog = now();
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();
        $afterLog = now();

        $log = ActivityLog::where('action', 'login')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertTrue($log->created_at->gte($beforeLog->subSecond()));
        $this->assertTrue($log->created_at->lte($afterLog->addSecond()));
    }
}
