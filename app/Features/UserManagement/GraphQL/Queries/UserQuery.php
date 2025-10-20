<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Helpers\JWTHelper;

/**
 * User Query
 *
 * Retorna información completa de un usuario por ID.
 * Misma estructura que 'me' para consistencia.
 * El @can directive en el schema valida permisos automáticamente.
 */
class UserQuery extends BaseQuery
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke($root, array $args)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $targetUser = $this->userService->getUserById($args['id']);

        // Company admin solo puede ver usuarios de su empresa
        if ($user->hasRole('COMPANY_ADMIN') && !$user->hasRole('PLATFORM_ADMIN')) {
            // Obtener las empresas del company_admin
            $adminCompanyIds = $user->userRoles()
                ->where('role_code', 'COMPANY_ADMIN')
                ->where('is_active', true)
                ->pluck('company_id')
                ->filter()
                ->toArray();

            // Obtener las empresas del usuario objetivo
            $targetCompanyIds = $targetUser->userRoles()
                ->where('is_active', true)
                ->whereNotNull('company_id')
                ->pluck('company_id')
                ->filter()
                ->toArray();

            // Verificar que al menos una empresa coincida
            $hasCommonCompany = !empty(array_intersect($adminCompanyIds, $targetCompanyIds));

            if (!$hasCommonCompany) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'No tienes permiso para ver este usuario'
                );
            }
        }

        return $targetUser;
    }
}