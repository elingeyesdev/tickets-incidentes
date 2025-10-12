<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Mutations\BaseMutation;

/**
 * Delete User Mutation V10.1
 *
 * Elimina lógicamente un usuario (soft delete).
 * Establece status como DELETED y anonimiza datos sensibles según GDPR.
 * Solo accesible por PLATFORM_ADMIN.
 */
class DeleteUserMutation extends BaseMutation
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * @param mixed $root
     * @param array{id: string, reason?: string} $args
     * @param mixed|null $context
     * @return bool
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke($root, array $args, $context = null): bool
    {
        // Authorization: Require PLATFORM_ADMIN only
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$user->hasRole('PLATFORM_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores de plataforma pueden eliminar usuarios'
            );
        }

        return $this->userService->deleteUser($args['id']);

        // Nota: El reason se registra automáticamente por la directiva @audit en el schema
    }
}