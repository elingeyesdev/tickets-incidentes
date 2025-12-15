<?php

namespace Tests\Feature\AuditLog;

use App\Features\AuditLog\Models\ActivityLog;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ActivityLogService Feature Tests
 *
 * Tests para verificar que todas las acciones del ActivityLogService
 * registran correctamente los logs con los datos esperados.
 */
class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $activityLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityLogService = app(ActivityLogService::class);
    }

    // ==================== AUTHENTICATION ACTIONS ====================

    /** @test */
    public function it_logs_login_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logLogin($user->id, [
            'device' => 'Chrome on Windows',
            'location' => 'La Paz, Bolivia',
        ]);

        // Flush buffer si usa Redis
        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);

        $log = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Chrome on Windows', $log->metadata['device']);
        $this->assertEquals('La Paz, Bolivia', $log->metadata['location']);
    }

    /** @test */
    public function it_logs_login_failed_action(): void
    {
        $this->activityLogService->logLoginFailed('attacker@example.com', 'Invalid credentials');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'action' => 'login_failed',
        ]);

        $log = ActivityLog::where('action', 'login_failed')->first();

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertEquals('attacker@example.com', $log->metadata['email']);
        $this->assertEquals('Invalid credentials', $log->metadata['reason']);
    }

    /** @test */
    public function it_logs_logout_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logLogout($user->id);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    /** @test */
    public function it_logs_register_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logRegister($user->id, $user->email);

        $this->activityLogService->flushBuffer();

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
        $this->assertEquals($user->email, $log->new_values['email']);
    }

    /** @test */
    public function it_logs_email_verified_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logEmailVerified($user->id);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'email_verified',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_logs_password_reset_requested_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logPasswordResetRequested($user->id, $user->email);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'password_reset_requested',
            'entity_type' => 'user',
        ]);

        $log = ActivityLog::where('action', 'password_reset_requested')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($user->email, $log->metadata['email']);
    }

    /** @test */
    public function it_logs_password_changed_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logPasswordChanged($user->id, 'reset');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'password_changed',
            'entity_type' => 'user',
        ]);

        $log = ActivityLog::where('action', 'password_changed')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('reset', $log->metadata['method']);
    }

    // ==================== TICKET ACTIONS ====================

    /** @test */
    public function it_logs_ticket_created_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->activityLogService->logTicketCreated($user->id, $ticket->id, [
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
            'priority' => $ticket->priority->value,
            'company_id' => $company->id,
        ]);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'ticket_created',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_created')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($ticket->ticket_code, $log->new_values['ticket_code']);
        $this->assertEquals($ticket->title, $log->new_values['title']);
        $this->assertEquals($ticket->priority->value, $log->new_values['priority']);
    }

    /** @test */
    public function it_logs_ticket_updated_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $oldData = [
            'ticket_code' => $ticket->ticket_code,
            'priority' => 'MEDIUM',
        ];

        $newData = [
            'ticket_code' => $ticket->ticket_code,
            'priority' => 'HIGH',
        ];

        $this->activityLogService->logTicketUpdated($user->id, $ticket->id, $oldData, $newData);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'ticket_updated',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_updated')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('MEDIUM', $log->old_values['priority']);
        $this->assertEquals('HIGH', $log->new_values['priority']);
    }

    /** @test */
    public function it_logs_ticket_assigned_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->activityLogService->logTicketAssigned($admin->id, $ticket->id, $agent->id);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'ticket_assigned',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_assigned')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($agent->id, $log->metadata['assigned_to']);
    }

    /** @test */
    public function it_logs_ticket_resolved_action(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->activityLogService->logTicketResolved($agent->id, $ticket->id, 'Problem fixed by updating configuration');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $agent->id,
            'action' => 'ticket_resolved',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_resolved')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Problem fixed by updating configuration', $log->metadata['resolution_note']);
    }

    /** @test */
    public function it_logs_ticket_closed_action(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->resolved()->create();

        $this->activityLogService->logTicketClosed($agent->id, $ticket->id, 'Closed after user confirmation');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $agent->id,
            'action' => 'ticket_closed',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_closed')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Closed after user confirmation', $log->metadata['close_note']);
    }

    /** @test */
    public function it_logs_ticket_reopened_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->resolved()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->activityLogService->logTicketReopened($user->id, $ticket->id, 'Issue reoccurred');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'ticket_reopened',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_reopened')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Issue reoccurred', $log->metadata['reopen_reason']);
    }

    /** @test */
    public function it_logs_ticket_response_added_action(): void
    {
        $agent = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $responseId = 'response-uuid-12345';

        $this->activityLogService->logTicketResponseAdded($agent->id, $ticket->id, $responseId);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $agent->id,
            'action' => 'ticket_response_added',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_response_added')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($responseId, $log->metadata['response_id']);
    }

    /** @test */
    public function it_logs_ticket_attachment_added_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
        ]);
        $attachmentId = 'attachment-uuid-12345';

        $this->activityLogService->logTicketAttachmentAdded(
            $user->id,
            $ticket->id,
            $attachmentId,
            'screenshot.png'
        );

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'ticket_attachment_added',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_attachment_added')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($attachmentId, $log->new_values['attachment_id']);
        $this->assertEquals('screenshot.png', $log->new_values['file_name']);
    }

    /** @test */
    public function it_logs_ticket_deleted_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $ticket = Ticket::factory()->create();

        $ticketData = [
            'ticket_code' => $ticket->ticket_code,
            'title' => $ticket->title,
        ];

        $this->activityLogService->logTicketDeleted($admin->id, $ticket->id, $ticketData);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'ticket_deleted',
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
        ]);

        $log = ActivityLog::where('action', 'ticket_deleted')
            ->where('entity_id', $ticket->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($ticket->ticket_code, $log->old_values['ticket_code']);
    }

    // ==================== USER MANAGEMENT ACTIONS ====================

    /** @test */
    public function it_logs_user_status_changed_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $targetUser = User::factory()->create();

        $this->activityLogService->logUserStatusChanged($admin->id, $targetUser->id, 'PENDING', 'ACTIVE');

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'user_status_changed',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $log = ActivityLog::where('action', 'user_status_changed')
            ->where('entity_id', $targetUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('PENDING', $log->old_values['status']);
        $this->assertEquals('ACTIVE', $log->new_values['status']);
    }

    /** @test */
    public function it_logs_role_assigned_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $targetUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->activityLogService->logRoleAssigned($admin->id, $targetUser->id, 'AGENT', $company->id);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'role_assigned',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $log = ActivityLog::where('action', 'role_assigned')
            ->where('entity_id', $targetUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('AGENT', $log->new_values['role']);
        $this->assertEquals($company->id, $log->new_values['company_id']);
    }

    /** @test */
    public function it_logs_role_removed_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $targetUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->activityLogService->logRoleRemoved($admin->id, $targetUser->id, 'COMPANY_ADMIN', $company->id);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'role_removed',
            'entity_type' => 'user',
            'entity_id' => $targetUser->id,
        ]);

        $log = ActivityLog::where('action', 'role_removed')
            ->where('entity_id', $targetUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('COMPANY_ADMIN', $log->old_values['role']);
        $this->assertEquals($company->id, $log->old_values['company_id']);
    }

    /** @test */
    public function it_logs_profile_updated_action(): void
    {
        $user = User::factory()->withProfile()->withRole('USER')->create();

        $oldValues = ['phone_number' => null];
        $newValues = ['phone_number' => '+591 70000000'];

        $this->activityLogService->logProfileUpdated($user->id, $oldValues, $newValues);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);

        $log = ActivityLog::where('action', 'profile_updated')
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->old_values['phone_number']);
        $this->assertEquals('+591 70000000', $log->new_values['phone_number']);
    }

    // ==================== COMPANY ACTIONS ====================

    /** @test */
    public function it_logs_company_request_approved_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();
        $requestId = 'request-uuid-12345';

        $this->activityLogService->logCompanyRequestApproved(
            $admin->id,
            $requestId,
            $company->name,
            $company->id,
            'admin@company.com'
        );

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'company_request_approved',
            'entity_type' => 'company_request',
            'entity_id' => $requestId,
        ]);

        $log = ActivityLog::where('action', 'company_request_approved')
            ->where('entity_id', $requestId)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->old_values['status']);
        $this->assertEquals('approved', $log->new_values['status']);
        $this->assertEquals($company->name, $log->new_values['company_name']);
        $this->assertEquals($company->id, $log->new_values['created_company_id']);
        $this->assertEquals('admin@company.com', $log->new_values['admin_email']);
    }

    /** @test */
    public function it_logs_company_request_rejected_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $requestId = 'request-uuid-67890';

        $this->activityLogService->logCompanyRequestRejected(
            $admin->id,
            $requestId,
            'Suspicious Company S.A.',
            'Incomplete documentation and inconsistent data'
        );

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'company_request_rejected',
            'entity_type' => 'company_request',
            'entity_id' => $requestId,
        ]);

        $log = ActivityLog::where('action', 'company_request_rejected')
            ->where('entity_id', $requestId)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('pending', $log->old_values['status']);
        $this->assertEquals('rejected', $log->new_values['status']);
        $this->assertEquals('Suspicious Company S.A.', $log->new_values['company_name']);
        $this->assertEquals('Incomplete documentation and inconsistent data', $log->new_values['reason']);
    }

    /** @test */
    public function it_logs_company_created_action(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $this->activityLogService->logCompanyCreated($admin->id, $company->id, $company->name);

        $this->activityLogService->flushBuffer();

        $this->assertDatabaseHas('audit.activity_logs', [
            'user_id' => $admin->id,
            'action' => 'company_created',
            'entity_type' => 'company',
            'entity_id' => $company->id,
        ]);

        $log = ActivityLog::where('action', 'company_created')
            ->where('entity_id', $company->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($company->name, $log->new_values['name']);
    }

    // ==================== LOG QUERYING ====================

    /** @test */
    public function it_can_query_user_activity(): void
    {
        $user = User::factory()->withRole('USER')->create();

        // Create multiple logs for the user
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logProfileUpdated($user->id, ['theme' => 'light'], ['theme' => 'dark']);
        $this->activityLogService->logLogout($user->id);

        $this->activityLogService->flushBuffer();

        $activity = $this->activityLogService->getUserActivity($user->id);

        $this->assertGreaterThanOrEqual(3, $activity->total());
    }

    /** @test */
    public function it_can_filter_user_activity_by_action(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogout($user->id);

        $this->activityLogService->flushBuffer();

        $loginActivity = $this->activityLogService->getUserActivity($user->id, 'login');

        foreach ($loginActivity as $log) {
            $this->assertEquals('login', $log->action);
        }
    }

    /** @test */
    public function it_can_filter_user_activity_by_category(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        // Auth action
        $this->activityLogService->logLogin($user->id);

        // Ticket action
        $this->activityLogService->logTicketCreated($user->id, $ticket->id, [
            'ticket_code' => $ticket->ticket_code,
        ]);

        $this->activityLogService->flushBuffer();

        $ticketActivity = $this->activityLogService->getUserActivity($user->id, null, 'tickets');

        foreach ($ticketActivity as $log) {
            $this->assertStringContainsString('ticket', $log->action);
        }
    }

    // ==================== MODEL ATTRIBUTES ====================

    /** @test */
    public function activity_log_has_correct_action_description(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $log = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->first();

        $this->assertEquals('Inicio de sesión', $log->actionDescription);
    }

    /** @test */
    public function activity_log_has_correct_action_category(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create();

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logTicketCreated($user->id, $ticket->id, []);

        $this->activityLogService->flushBuffer();

        $loginLog = ActivityLog::where('action', 'login')->first();
        $ticketLog = ActivityLog::where('action', 'ticket_created')->first();

        $this->assertEquals('authentication', $loginLog->actionCategory);
        $this->assertEquals('tickets', $ticketLog->actionCategory);
    }

    // ==================== SCOPES ====================

    /** @test */
    public function it_can_scope_auth_actions(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create();

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogout($user->id);
        $this->activityLogService->logTicketCreated($user->id, $ticket->id, []);

        $this->activityLogService->flushBuffer();

        $authLogs = ActivityLog::authActions()->get();

        foreach ($authLogs as $log) {
            $this->assertContains($log->action, [
                'login', 'login_failed', 'logout', 'register',
                'email_verified', 'password_reset_requested', 'password_changed',
            ]);
        }
    }

    /** @test */
    public function it_can_scope_ticket_actions(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create();

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logTicketCreated($user->id, $ticket->id, []);
        $this->activityLogService->logTicketUpdated($user->id, $ticket->id, [], []);

        $this->activityLogService->flushBuffer();

        $ticketLogs = ActivityLog::ticketActions()->get();

        foreach ($ticketLogs as $log) {
            $this->assertStringContainsString('ticket', $log->action);
        }
    }

    /** @test */
    public function it_can_scope_company_actions(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        $this->activityLogService->logLogin($admin->id);
        $this->activityLogService->logCompanyCreated($admin->id, $company->id, $company->name);

        $this->activityLogService->flushBuffer();

        $companyLogs = ActivityLog::companyActions()->get();

        foreach ($companyLogs as $log) {
            $this->assertContains($log->action, [
                'company_created', 'company_request_approved', 'company_request_rejected',
            ]);
        }
    }
}
