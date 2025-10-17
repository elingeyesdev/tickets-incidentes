<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Resolvers;

use App\Features\UserManagement\Models\User;

/**
 * Resolver para language en UserAuthInfo
 *
 * Resuelve el idioma preferido del usuario desde su perfil
 */
class LanguageResolver
{
    public function __invoke(User $user): string
    {
        return $user->profile?->language ?? 'es';
    }
}
