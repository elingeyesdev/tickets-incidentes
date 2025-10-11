<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\RoleService;
use App\Shared\GraphQL\Mutations\BaseMutation;

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
     */
    public function __invoke($root, array $args, $context = null)
    {
        return $this->roleService->removeRoleById(
            roleId: $args['roleId'],
            reason: $args['reason'] ?? null
        );
    }
}
