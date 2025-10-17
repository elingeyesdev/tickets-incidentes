<?php

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

/**
 * Resolver para el campo onboardingCompleted en UserAuthInfo
 *
 * Este resolver mapea el campo snake_case de la BD (onboarding_completed)
 * al campo camelCase de GraphQL (onboardingCompleted)
 */
class OnboardingCompletedResolver
{
    /**
     * Resolver para el campo onboardingCompleted
     *
     * @param User|array $root El modelo User o array del parent
     * @return bool
     */
    public function __invoke($root): bool
    {
        // Si es un array, verificar que onboarding_completed_at no sea null
        if (is_array($root)) {
            return isset($root['onboarding_completed_at']) && $root['onboarding_completed_at'] !== null;
        }

        // Si es un modelo User, verificar que el timestamp no sea null
        if ($root instanceof User) {
            return $root->onboarding_completed_at !== null;
        }

        return false;
    }
}
