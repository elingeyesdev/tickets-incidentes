<?php

namespace Tests;

use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

abstract class TestCase extends BaseTestCase
{
    use MakesGraphQLRequests, \Tests\Traits\HandlesTimeTravelWithCache;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * IMPORTANT: Set to true to ensure roles are seeded in test database.
     * The create_roles_table migration inserts roles, but RefreshDatabase
     * drops all tables before tests. The DatabaseSeeder re-seeds essential data.
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * NOTE: Roles are seeded via DatabaseSeeder (calls RolesSeeder)
     * This ensures auth.roles table has data after RefreshDatabase runs.
     */

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Force JWT blacklist to be enabled in tests
        // This ensures logout invalidation works correctly in tests
        config(['jwt.blacklist_enabled' => true]);

        // Disable rate limiting in tests to prevent "Too Many Attempts" errors
        // Clear all rate limiter attempts at the start of each test
        \Illuminate\Support\Facades\Cache::flush();
    }

    /**
     * Authenticate a user for GraphQL testing
     *
     * This method simulates authentication by setting the user in Laravel's Auth,
     * which is compatible with the @jwt directive in testing environments.
     *
     * @param  User  $user  The user to authenticate
     * @param  string|null  $guard  The guard to use (default: null)
     */
    protected function actingAsGraphQL(User $user, ?string $guard = null): self
    {
        // Set the authenticated user using Laravel's Auth
        // The @jwt directive will detect this in testing environments
        Auth::guard($guard)->setUser($user);

        return $this;
    }

    /**
     * Generate a valid JWT token for testing
     *
     * Useful for testing token-based authentication flows.
     * Returns the access token string that can be used in Authorization headers.
     *
     * @param  User  $user  The user to generate the token for
     * @return string The JWT access token
     */
    protected function generateAccessToken(User $user): string
    {
        $tokenService = app(TokenService::class);

        return $tokenService->generateAccessToken($user);
    }

    /**
     * Authenticate a user for testing using JWT token
     *
     * This method generates a real JWT token and adds it to the Authorization header
     * for all subsequent requests. This is the recommended way to authenticate users
     * in tests as it matches production authentication flow.
     *
     * IMPORTANT: The JWTAuthenticationMiddleware (configured in lighthouse.php) will
     * automatically process this token and set the necessary request attributes that
     * the @jwt directive expects.
     *
     * @param  User  $user  The user to authenticate
     * @return $this
     */
    protected function authenticateWithJWT(User $user): self
    {
        // Generate a real JWT token using TokenService
        $tokenService = app(TokenService::class);

        // Generate access token with test session ID
        $sessionId = 'test_session_'.uniqid();
        $token = $tokenService->generateAccessToken($user, $sessionId);

        // Add Authorization header to all subsequent requests
        // The RequireJWTAuthentication middleware will automatically:
        // 1. Extract the token from the Authorization header
        // 2. Validate the token using TokenService
        // 3. Decode and store the JWT payload in request()->attributes->set('jwt_payload', ...)
        // 4. Set the authenticated user via auth()->setUser($user)
        //
        // Controllers can then access the JWT payload via:
        // $payload = request()->attributes->get('jwt_payload');
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Clear JWT authentication headers
     *
     * This method removes the Authorization header that was added by authenticateWithJWT().
     * Use this when you need to make unauthenticated requests after authenticated ones.
     *
     * @return $this
     */
    protected function clearJWTAuth(): self
    {
        return $this->withoutHeader('Authorization');
    }

    /**
     * Create a company admin user with a company
     *
     * Useful for testing endpoints that require COMPANY_ADMIN role.
     * Returns a user with COMPANY_ADMIN role for their company.
     *
     * @return User The company admin user
     */
    protected function createCompanyAdmin(): User
    {
        $user = User::factory()->create();
        $company = \App\Features\CompanyManagement\Models\Company::factory()->create(['admin_user_id' => $user->id]);
        $user->assignRole('COMPANY_ADMIN', $company->id);

        return $user;
    }

    /**
     * Create a maintenance announcement via HTTP POST endpoint
     *
     * Uses HTTP POST to create announcements, which ensures proper transaction
     * handling with RefreshDatabase trait. This is the correct approach for
     * testing because:
     * 1. It tests the actual HTTP flow (like production)
     * 2. It avoids RefreshDatabase transaction isolation issues
     * 3. All subsequent route model binding works correctly
     *
     * @param  User  $user  The authenticated user creating the announcement
     * @param  array  $overrides  Override default payload values
     * @param  string  $action  'draft', 'publish', or 'schedule'
     * @return \App\Features\ContentManagement\Models\Announcement The created announcement
     */
    protected function createMaintenanceAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): \App\Features\ContentManagement\Models\Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test Maintenance',
            'content' => 'Test content',
            'urgency' => 'MEDIUM',
            'scheduled_start' => now()->addDays(1)->toIso8601String(),
            'scheduled_end' => now()->addDays(1)->addHours(2)->toIso8601String(),
            'is_emergency' => false,
            'affected_services' => [],
        ], $overrides);

        // Add action if not draft
        if ($action !== 'draft') {
            $payload['action'] = $action;
        }

        // Add scheduled_for if provided and action is schedule
        if ($scheduledFor && $action === 'schedule') {
            $payload['scheduled_for'] = $scheduledFor;
        }

        // Make HTTP POST request
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/announcements/maintenance', $payload);

        // Assert the request was successful
        if (! in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create announcement via HTTP. Status: {$response->status()}\n".
                "Response: {$response->content()}"
            );
        }

        // Extract the ID from response
        $announcementId = $response->json('data.id');

        if (! $announcementId) {
            throw new \Exception(
                "No announcement ID in response.\n".
                "Response: {$response->content()}"
            );
        }

        // Fetch the created announcement from database
        $announcement = \App\Features\ContentManagement\Models\Announcement::findOrFail($announcementId);

        // CRITICAL: Clear JWT auth headers to prevent pollution in subsequent requests
        // This ensures that tests making unauthenticated requests work correctly
        $this->clearJWTAuth();

        return $announcement;
    }

    /**
     * Create an incident announcement via HTTP POST endpoint
     *
     * Uses HTTP POST to create announcements, ensuring proper transaction
     * handling with RefreshDatabaseWithoutTransactions trait.
     *
     * @param  User  $user  The authenticated user creating the incident
     * @param  array  $overrides  Override default payload values
     * @param  string  $action  'draft', 'publish', or 'schedule'
     * @param  string|null  $scheduledFor  ISO8601 datetime for scheduled publication
     * @return \App\Features\ContentManagement\Models\Announcement The created incident announcement
     */
    protected function createIncidentAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): \App\Features\ContentManagement\Models\Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test Incident',
            'content' => 'Test incident content',
            'urgency' => 'MEDIUM',
            'is_resolved' => false,
            'started_at' => now()->subHours(1)->toIso8601String(),
            'affected_services' => [],
        ], $overrides);

        // Add action if not draft
        if ($action !== 'draft') {
            $payload['action'] = $action;
        }

        // Add scheduled_for if provided and action is schedule
        if ($scheduledFor && $action === 'schedule') {
            $payload['scheduled_for'] = $scheduledFor;
        }

        // Make HTTP POST request
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/announcements/incidents', $payload);

        // Assert the request was successful
        if (! in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create incident via HTTP. Status: {$response->status()}\n".
                "Response: {$response->content()}"
            );
        }

        // Extract the ID from response
        $announcementId = $response->json('data.id');

        if (! $announcementId) {
            throw new \Exception(
                "No announcement ID in response.\n".
                "Response: {$response->content()}"
            );
        }

        // Fetch the created announcement from database
        $announcement = \App\Features\ContentManagement\Models\Announcement::findOrFail($announcementId);

        // CRITICAL: Clear JWT auth headers to prevent pollution in subsequent requests
        // This ensures that tests making unauthenticated requests work correctly
        $this->clearJWTAuth();

        return $announcement;
    }

    /**
     * Create a news announcement via HTTP POST endpoint
     *
     * Uses HTTP POST to create announcements, which ensures proper transaction
     * handling with RefreshDatabaseWithoutTransactions trait. This is the correct
     * approach for testing because:
     * 1. It tests the actual HTTP flow (like production)
     * 2. It avoids RefreshDatabase transaction isolation issues
     * 3. All subsequent route model binding works correctly
     *
     * @param User $user The authenticated user creating the announcement
     * @param array $overrides Override default payload values
     * @param string $action 'draft', 'publish', or 'schedule'
     * @param string|null $scheduledFor ISO8601 datetime for scheduled publication
     * @return \App\Features\ContentManagement\Models\Announcement The created announcement
     */
    protected function createNewsAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): \App\Features\ContentManagement\Models\Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test News',
            'body' => 'Test news content',
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users'],
                'summary' => 'Test news summary',
            ],
        ], $overrides);

        // Add action if not draft
        if ($action !== 'draft') {
            $payload['action'] = $action;
        }

        // Add scheduled_for if provided and action is schedule
        if ($scheduledFor && $action === 'schedule') {
            $payload['scheduled_for'] = $scheduledFor;
        }

        // Make HTTP POST request
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/announcements/news', $payload);

        // Assert the request was successful
        if (!in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create news via HTTP. Status: {$response->status()}\n" .
                "Response: {$response->content()}"
            );
        }

        // Extract the ID from response
        $announcementId = $response->json('data.id');

        if (!$announcementId) {
            throw new \Exception(
                "No announcement ID in response.\n" .
                "Response: {$response->content()}"
            );
        }

        // Fetch the created announcement from database
        $announcement = \App\Features\ContentManagement\Models\Announcement::findOrFail($announcementId);

        // CRITICAL: Clear JWT auth headers to prevent pollution in subsequent requests
        // This ensures that tests making unauthenticated requests work correctly
        $this->clearJWTAuth();

        return $announcement;
    }

    /**
     * Create an alert announcement via HTTP POST endpoint
     *
     * Uses HTTP POST to create announcements, which ensures proper transaction
     * handling with RefreshDatabaseWithoutTransactions trait. This is the correct
     * approach for testing because:
     * 1. It tests the actual HTTP flow (like production)
     * 2. It avoids RefreshDatabase transaction isolation issues
     * 3. All subsequent route model binding works correctly
     *
     * @param User $user The authenticated user creating the announcement
     * @param array $overrides Override default payload values
     * @param string $action 'draft', 'publish', or 'schedule'
     * @param string|null $scheduledFor ISO8601 datetime for scheduled publication
     * @return \App\Features\ContentManagement\Models\Announcement The created announcement
     */
    protected function createAlertAnnouncementViaHttp(
        User $user,
        array $overrides = [],
        string $action = 'draft',
        ?string $scheduledFor = null
    ): \App\Features\ContentManagement\Models\Announcement {
        // Build payload with defaults
        $payload = array_merge([
            'title' => 'Test Alert',
            'content' => 'Test alert content',
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'security',
                'message' => 'Test alert message with minimum length',
                'action_required' => false,
                'started_at' => now()->addHour()->toIso8601String(),
            ],
        ], $overrides);

        // Add action if not draft
        if ($action !== 'draft') {
            $payload['action'] = $action;
        }

        // Add scheduled_for if provided and action is schedule
        if ($scheduledFor && $action === 'schedule') {
            $payload['scheduled_for'] = $scheduledFor;
        }

        // Make HTTP POST request
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/announcements/alerts', $payload);

        // Assert the request was successful
        if (!in_array($response->status(), [201])) {
            throw new \Exception(
                "Failed to create alert via HTTP. Status: {$response->status()}\n" .
                "Response: {$response->content()}"
            );
        }

        // Extract the ID from response
        $announcementId = $response->json('data.id');

        if (!$announcementId) {
            throw new \Exception(
                "No announcement ID in response.\n" .
                "Response: {$response->content()}"
            );
        }

        // Fetch the created announcement from database
        $announcement = \App\Features\ContentManagement\Models\Announcement::findOrFail($announcementId);

        // CRITICAL: Clear JWT auth headers to prevent pollution in subsequent requests
        // This ensures that tests making unauthenticated requests work correctly
        $this->clearJWTAuth();

        return $announcement;
    }

    /**
     * Execute all queued jobs manually (for testing)
     *
     * When using Queue::fake(), jobs are intercepted but not executed.
     * This helper method manually executes queued jobs from the fake queue
     * so their side effects (like Mail::send()) occur during testing.
     */
    protected function executeQueuedJobs(): void
    {
        // Get the queue manager
        $queueManager = app('queue');

        // Check if QueueFake is being used
        if (! $queueManager instanceof \Illuminate\Support\Testing\Fakes\QueueFake) {
            // Queue::fake() is not active, so there's nothing to execute
            return;
        }

        // Use reflection to access protected properties since QueueFake doesn't expose them
        $reflection = new \ReflectionClass($queueManager);
        $pushedJobsProperty = $reflection->getProperty('jobs');
        $pushedJobsProperty->setAccessible(true);
        $pushedJobs = $pushedJobsProperty->getValue($queueManager);

        // Execute each job
        foreach ($pushedJobs as $queueName => $jobsList) {
            foreach ($jobsList as $jobData) {
                if (isset($jobData['job'])) {
                    $job = $jobData['job'];

                    // Execute the job's handle method
                    if (method_exists($job, 'handle')) {
                        try {
                            app()->call([$job, 'handle']);
                        } catch (\Exception $e) {
                            // Log but don't fail the test
                            logger()->error('Queue job execution failed in test: '.$e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
