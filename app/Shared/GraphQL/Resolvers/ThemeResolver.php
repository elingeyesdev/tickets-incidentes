<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

/**
 * Resolver para theme en UserAuthInfo
 *
 * Resuelve el tema preferido del usuario desde su perfil
 */
class ThemeResolver
{
    public function __invoke(User $user): string
    {
        return $user->profile?->theme ?? 'light';
    }
}
