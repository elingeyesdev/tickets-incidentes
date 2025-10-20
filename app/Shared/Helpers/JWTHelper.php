<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Features\UserManagement\Models\User;
use Illuminate\Auth\AuthenticationException;

/**
 * JWT Helper Class
 *
 * Provides static utility methods for JWT authentication.
 * Retrieves authenticated user information from request attributes
 * set by JWTAuthenticationMiddleware.
 *
 * @package App\Shared\Helpers
 */
class JWTHelper
{
    /**
     * Get the authenticated user from the request.
     *
     * @return User The authenticated user instance
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getAuthenticatedUser(): User
    {
        $user = request()->attributes->get('jwt_user');

        if (!$user instanceof User) {
            throw new AuthenticationException('User is not authenticated');
        }

        return $user;
    }

    /**
     * Check if the current request has an authenticated user.
     *
     * @return bool True if user is authenticated, false otherwise
     */
    public static function isAuthenticated(): bool
    {
        $user = request()->attributes->get('jwt_user');

        return $user instanceof User;
    }

    /**
     * Get the authenticated user's ID.
     *
     * @return string The user's UUID
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getUserId(): string
    {
        $user = self::getAuthenticatedUser();

        return $user->id;
    }

    /**
     * Check if the authenticated user has a specific role.
     *
     * @param string $roleCode The role code to check (e.g., 'PLATFORM_ADMIN')
     * @return bool True if user has the role, false otherwise
     * @throws AuthenticationException If user is not authenticated
     */
    public static function hasRole(string $roleCode): bool
    {
        $user = self::getAuthenticatedUser();

        // Ensure roles relationship is loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->contains('role_code', $roleCode);
    }

    /**
     * Check if the authenticated user has any of the specified roles.
     *
     * @param array<string> $roleCodes Array of role codes to check
     * @return bool True if user has at least one of the roles, false otherwise
     * @throws AuthenticationException If user is not authenticated
     */
    public static function hasAnyRole(array $roleCodes): bool
    {
        $user = self::getAuthenticatedUser();

        // Ensure roles relationship is loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        $userRoleCodes = $user->roles->pluck('role_code')->toArray();

        return !empty(array_intersect($userRoleCodes, $roleCodes));
    }
}
