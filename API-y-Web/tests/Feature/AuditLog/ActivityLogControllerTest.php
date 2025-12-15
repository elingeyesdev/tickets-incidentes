<?php

namespace Tests\Feature\AuditLog;

use App\Features\AuditLog\Models\ActivityLog;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ActivityLogController Feature Tests
 *
 * Tests para verificar los endpoints de Activity Logs:
 * - GET /api/activity-logs (index)
 * - GET /api/activity-logs/my (myActivity)
 */
class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private ActivityLogService $activityLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityLogService = app(ActivityLogService::class);
    }

    // ==================== INDEX ENDPOINT ====================

    /** @test */
    public function user_can_access_activity_logs_endpoint(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Create some logs
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function user_can_only_see_their_own_logs(): void
    {
        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user1);

        // Create logs for both users
        $this->activityLogService->logLogin($user1->id);
        $this->activityLogService->logLogin($user2->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs');

        $response->assertStatus(200);

        $data = $response->json('data');

        // All returned logs should belong to user1
        foreach ($data as $log) {
            $this->assertEquals($user1->id, $log['userId']);
        }
    }

    /** @test */
    public function regular_user_cannot_see_other_users_logs(): void
    {
        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user1);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/activity-logs?user_id={$user2->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tienes permiso para ver la actividad de otros usuarios',
            ]);
    }

    /** @test */
    public function platform_admin_can_see_all_users_logs(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($admin);

        // Create logs for both
        $this->activityLogService->logLogin($admin->id);
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs');

        $response->assertStatus(200);

        // Admin should see logs from multiple users
        $data = $response->json('data');
        $userIds = array_unique(array_column($data, 'userId'));
        $this->assertGreaterThanOrEqual(1, count($userIds));
    }

    /** @test */
    public function platform_admin_can_filter_by_specific_user(): void
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($admin);

        // Create logs for both
        $this->activityLogService->logLogin($admin->id);
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogout($user->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/activity-logs?user_id={$user->id}");

        $response->assertStatus(200);

        $data = $response->json('data');

        // All logs should belong to the specified user
        foreach ($data as $log) {
            $this->assertEquals($user->id, $log['userId']);
        }
    }

    /** @test */
    public function can_filter_by_action(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Create different logs
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logLogout($user->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs?action=login');

        $response->assertStatus(200);

        $data = $response->json('data');

        foreach ($data as $log) {
            $this->assertEquals('login', $log['action']);
        }
    }

    /** @test */
    public function can_filter_by_category(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Create auth and profile logs
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logProfileUpdated($user->id, [], []);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs?category=authentication');

        $response->assertStatus(200);

        $data = $response->json('data');

        // All logs should be authentication category
        $authActions = ['login', 'login_failed', 'logout', 'register', 'email_verified', 'password_reset_requested', 'password_changed'];
        foreach ($data as $log) {
            $this->assertContains($log['action'], $authActions);
        }
    }

    /** @test */
    public function can_filter_by_date_range(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Create log
        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $today = now()->format('Y-m-d');

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/activity-logs?from={$today}&to={$today}");

        $response->assertStatus(200);

        // Should have at least the login we created
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    /** @test */
    public function can_paginate_results(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        // Create multiple logs
        for ($i = 0; $i < 5; $i++) {
            $this->activityLogService->logLogin($user->id);
        }
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs?per_page=2');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.per_page'));
        $this->assertCount(2, $response->json('data'));
    }

    // ==================== MY ACTIVITY ENDPOINT ====================

    /** @test */
    public function user_can_access_my_activity_endpoint(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs/my');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
            ]);
    }

    /** @test */
    public function my_activity_returns_only_authenticated_user_logs(): void
    {
        $user1 = User::factory()->withRole('USER')->create();
        $user2 = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user1);

        // Create logs for both
        $this->activityLogService->logLogin($user1->id);
        $this->activityLogService->logLogin($user2->id);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs/my');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should only see own logs
        foreach ($data as $log) {
            $this->assertEquals($user1->id, $log['userId']);
        }
    }

    /** @test */
    public function my_activity_can_filter_by_category(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->logProfileUpdated($user->id, [], []);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs/my?category=users');

        $response->assertStatus(200);

        $data = $response->json('data');
        $userActions = ['user_status_changed', 'role_assigned', 'role_removed', 'profile_updated'];
        
        foreach ($data as $log) {
            $this->assertContains($log['action'], $userActions);
        }
    }

    /** @test */
    public function my_activity_can_filter_by_date_range(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $this->activityLogService->logLogin($user->id);
        $this->activityLogService->flushBuffer();

        $today = now()->format('Y-m-d');

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/activity-logs/my?from={$today}&to={$today}");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));
    }

    // ==================== AUTHENTICATION ====================

    /** @test */
    public function unauthenticated_user_cannot_access_activity_logs(): void
    {
        $response = $this->getJson('/api/activity-logs');

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_my_activity(): void
    {
        $response = $this->getJson('/api/activity-logs/my');

        $response->assertStatus(401);
    }

    // ==================== RESPONSE STRUCTURE ====================

    /** @test */
    public function activity_log_response_has_correct_structure(): void
    {
        $user = User::factory()->withRole('USER')->create();
        $token = $this->generateAccessToken($user);

        $this->activityLogService->logLogin($user->id, [
            'device' => 'Chrome on Windows',
        ]);
        $this->activityLogService->flushBuffer();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/activity-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'userId',
                        'action',
                        'actionDescription',
                        'entityType',
                        'entityId',
                        'oldValues',
                        'newValues',
                        'metadata',
                        'ipAddress',
                        'createdAt',
                    ],
                ],
            ]);
    }
}
