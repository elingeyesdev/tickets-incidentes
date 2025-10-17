<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

/**
 * Resolver para avatarUrl en UserAuthInfo
 *
 * Resuelve la URL del avatar desde el perfil del usuario
 */
class AvatarUrlResolver
{
    public function __invoke(User $user): ?string
    {
        return $user->profile?->avatar_url;
    }
}
