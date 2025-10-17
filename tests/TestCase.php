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
     * NOTE: Roles are automatically inserted by the create_roles_table migration
     * No need to manually seed them here.
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
}
