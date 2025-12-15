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

    /**
     * Get all roles from the JWT token.
     *
     * Retrieves roles from JWT payload (not from database).
     * Format: [["code" => "COMPANY_ADMIN", "company_id" => "uuid"], ...]
     *
     * @return array Array of roles with code and company_id
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getRoles(): array
    {
        $payload = request()->attributes->get('jwt_payload');

        if (!$payload) {
            throw new AuthenticationException('JWT payload not found in request');
        }

        $roles = $payload['roles'] ?? [['code' => 'USER', 'company_id' => null]];

        // Convert stdClass objects to arrays (JWT decodes objects as stdClass by default)
        return array_map(function($role) {
            if (is_object($role)) {
                return (array) $role;
            }
            return $role;
        }, $roles);
    }

    /**
     * Check if the user has a specific role in the JWT token.
     *
     * Checks JWT payload directly (stateless verification).
     *
     * @param string $roleCode The role code to check (e.g., 'COMPANY_ADMIN')
     * @return bool True if user has the role in JWT, false otherwise
     * @throws AuthenticationException If user is not authenticated
     */
    public static function hasRoleFromJWT(string $roleCode): bool
    {
        $roles = self::getRoles();

        return !empty(array_filter($roles, fn($role) => $role['code'] === $roleCode));
    }

    /**
     * Get the company ID for a specific role from the JWT token.
     *
     * Useful for checking company context from JWT without DB query.
     *
     * @param string $roleCode The role code (e.g., 'COMPANY_ADMIN')
     * @return string|null The company_id or null if role not found or has no company
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getCompanyIdFromJWT(string $roleCode): ?string
    {
        $roles = self::getRoles();

        $role = collect($roles)->firstWhere('code', $roleCode);

        return $role['company_id'] ?? null;
    }

    /**
     * Get all company IDs for a specific role code from JWT.
     *
     * Useful if user has multiple instances of same role in different companies.
     *
     * @param string $roleCode The role code to filter by
     * @return array Array of company UUIDs for that role
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getCompanyIdsForRole(string $roleCode): array
    {
        $roles = self::getRoles();

        return collect($roles)
            ->where('code', $roleCode)
            ->pluck('company_id')
            ->filter()
            ->values()
            ->toArray();
    }

    // ==================== ACTIVE ROLE METHODS (NEW) ====================

    /**
     * Get the active role from JWT.
     *
     * Returns the role currently selected by the user.
     * If no active_role claim exists (backward compatibility), falls back to
     * the first role in the roles array.
     *
     * @return array ['code' => string, 'company_id' => ?string]
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getActiveRole(): array
    {
        $payload = request()->attributes->get('jwt_payload');

        if (!$payload) {
            throw new AuthenticationException('JWT payload not found in request');
        }

        // Check if active_role exists in JWT (new system)
        if (isset($payload['active_role'])) {
            $activeRole = $payload['active_role'];
            
            // Convert stdClass to array if needed
            if (is_object($activeRole)) {
                return (array) $activeRole;
            }
            
            return $activeRole;
        }

        // Backward compatibility: If no active_role, use first available role
        // This ensures existing JWTs without active_role still work
        $roles = self::getRoles();
        
        if (empty($roles)) {
            return ['code' => 'USER', 'company_id' => null];
        }

        return $roles[0];
    }

    /**
     * Get the active role code.
     *
     * Returns the code of the role currently active (e.g., 'COMPANY_ADMIN').
     *
     * @return string The active role code
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getActiveRoleCode(): string
    {
        return self::getActiveRole()['code'];
    }

    /**
     * Get the company_id of the active role.
     *
     * Returns the company ID associated with the active role, or null
     * if the active role doesn't have a company context (PLATFORM_ADMIN, USER).
     *
     * @return string|null The company UUID or null
     * @throws AuthenticationException If user is not authenticated
     */
    public static function getActiveCompanyId(): ?string
    {
        return self::getActiveRole()['company_id'] ?? null;
    }

    /**
     * Check if the active role matches the given role code.
     *
     * @param string $roleCode The role code to check against (e.g., 'AGENT')
     * @return bool True if the active role matches, false otherwise
     * @throws AuthenticationException If user is not authenticated
     */
    public static function isActiveRole(string $roleCode): bool
    {
        return self::getActiveRoleCode() === $roleCode;
    }

    /**
     * Check if the active role is one of the given role codes.
     *
     * @param array<string> $roleCodes Array of role codes to check
     * @return bool True if the active role is in the array, false otherwise
     * @throws AuthenticationException If user is not authenticated
     */
    public static function isActiveRoleOneOf(array $roleCodes): bool
    {
        return in_array(self::getActiveRoleCode(), $roleCodes, true);
    }
}
