<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Services\RoleService;
use App\Shared\GraphQL\Queries\BaseQuery;
use Illuminate\Support\Facades\Auth;

/**
 * Available Roles Query
 *
 * Retorna la lista de roles disponibles en el sistema.
 * Cache de 1 hora (definido en schema con @cache).
 */
class AvailableRolesQuery extends BaseQuery
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function __invoke($root, array $args)
    {
        // Authorization: Require PLATFORM_ADMIN or COMPANY_ADMIN
        $user = Auth::user();

        if (!$user) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$user->hasRole('PLATFORM_ADMIN') && !$user->hasRole('COMPANY_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores pueden consultar roles disponibles'
            );
        }

        $roles = $this->roleService->getAllRoles();

        // Transformar a formato RoleInfo del schema
        return $roles->map(function ($role) {
            return [
                'code' => $role->role_code,
                'name' => $role->role_name,
                'description' => $role->description,
                'requiresCompany' => $role->requiresCompany(),
                'defaultDashboard' => $this->getDefaultDashboard($role->role_code),
                'isSystemRole' => $role->is_system,
            ];
        })->toArray();
    }

    /**
     * Obtiene el dashboard por defecto según el rol
     */
    private function getDefaultDashboard(string $roleCode): string
    {
        return match($roleCode) {
            'PLATFORM_ADMIN' => '/platform/dashboard',
            'COMPANY_ADMIN' => '/company/dashboard',
            'AGENT' => '/company/tickets',
            'USER' => '/my/tickets',
            default => '/dashboard',
        };
    }
}