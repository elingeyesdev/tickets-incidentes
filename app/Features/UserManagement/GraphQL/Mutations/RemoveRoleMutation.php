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

        // Company admin solo puede remover roles de su empresa
        if ($user->hasRole('COMPANY_ADMIN') && !$user->hasRole('PLATFORM_ADMIN')) {
            $targetRole = \App\Features\UserManagement\Models\UserRole::find($args['roleId']);

            if (!$targetRole) {
                throw new \App\Shared\Exceptions\NotFoundException('Rol no encontrado');
            }

            // Obtener las empresas del company_admin
            $adminCompanyIds = $user->userRoles()
                ->where('role_code', 'COMPANY_ADMIN')
                ->where('is_active', true)
                ->pluck('company_id')
                ->filter()
                ->toArray();

            // Verificar que el rol a remover pertenece a una de sus empresas
            if (!in_array($targetRole->company_id, $adminCompanyIds)) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'No tienes permiso para remover este rol'
                );
            }
        }

        return $this->roleService->removeRoleById(
            roleId: $args['roleId'],
            reason: $args['reason'] ?? null
        );
    }
}
