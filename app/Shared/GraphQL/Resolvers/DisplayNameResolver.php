<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

/**
 * Resolver para displayName en UserAuthInfo
 *
 * Resuelve el nombre completo del usuario desde su perfil
 */
class DisplayNameResolver
{
    public function __invoke(User $user): string
    {
        if ($user->profile) {
            return trim("{$user->profile->first_name} {$user->profile->last_name}");
        }

        return $user->email;
    }
}
