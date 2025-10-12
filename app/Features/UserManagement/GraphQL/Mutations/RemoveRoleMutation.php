<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\RoleService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use Illuminate\Support\Facades\Auth;

/**
 * Remove Role Mutation
 *
 * Remueve un rol de un usuario (soft delete - reversible)
 * Para reactivarlo, usar assignRole con los mismos parámetros
 */
class RemoveRoleMutation extends BaseMutation
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * @param mixed $root
     * @param array{roleId: string, reason: string|null} $args
     * @param mixed|null $context
     * @return bool
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke($root, array $args, $context = null)
    {
        // Authorization: Require PLATFORM_ADMIN or COMPANY_ADMIN
        $user = Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$user->hasRole('PLATFORM_ADMIN') && !$user->hasRole('COMPANY_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores pueden remover roles'
            );
        }

        return $this->roleService->removeRoleById(
            roleId: $args['roleId'],
            reason: $args['reason'] ?? null
        );
    }
}
