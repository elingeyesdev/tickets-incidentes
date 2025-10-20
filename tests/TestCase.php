<?php

namespace Tests;

use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;

abstract class TestCase extends BaseTestCase
{
    use MakesGraphQLRequests;

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
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Force JWT blacklist to be enabled in tests
        // This ensures logout invalidation works correctly in tests
        config(['jwt.blacklist_enabled' => true]);
    }

    /**
     * Authenticate a user for GraphQL testing
     *
     * This method simulates authentication by setting the user in Laravel's Auth,
     * which is compatible with the @jwt directive in testing environments.
     *
     * @param User $user The user to authenticate
     * @param string|null $guard The guard to use (default: null)
     * @return self
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
     * @param User $user The user to generate the token for
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
     * @param User $user The user to authenticate
     * @return $this
     */
    protected function authenticateWithJWT(User $user): self
    {
        // Generate a real JWT token using TokenService
        $tokenService = app(TokenService::class);

        // Generate access token with test session ID
        $token = $tokenService->generateAccessToken($user, 'test_session_' . uniqid());

        // Add Authorization header to all subsequent requests
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);

        return $this;
    }
}
