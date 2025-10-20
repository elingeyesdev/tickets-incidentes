<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Models\User;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Helpers\JWTHelper;

/**
 * Users Query V10.1
 *
 * Lista paginada de usuarios con filtros avanzados.
 * Solo accesible por PLATFORM_ADMIN o COMPANY_ADMIN.
 * Company admins solo pueden ver usuarios de su empresa.
 */
class UsersQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // Authorization: Require PLATFORM_ADMIN or COMPANY_ADMIN
        $authUser = JWTHelper::getAuthenticatedUser();

        if (!$authUser) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$authUser->hasRole('PLATFORM_ADMIN') && !$authUser->hasRole('COMPANY_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores pueden listar usuarios'
            );
        }

        $perPage = min($args['first'] ?? 15, 50); // Máximo 50 (definido en schema)
        $page = $args['page'] ?? 1;
        $filters = $args['filters'] ?? [];
        $orderBy = $args['orderBy'] ?? [];

        // Iniciar query
        // IMPORTANTE: Filtrar solo usuarios con profile (User.profile es non-nullable en schema)
        $query = User::query()
            ->has('profile') // Solo usuarios con profile
            ->with('profile');

        // Aplicar filtros
        $this->applyFilters($query, $filters, $authUser);

        // Aplicar ordenamiento
        $this->applyOrdering($query, $orderBy);

        // Paginar
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->items(),
            'paginatorInfo' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'hasMorePages' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Aplica filtros al query de usuarios
     */
    private function applyFilters($query, array $filters, $authUser): void
    {
        // Búsqueda de texto (email, nombre, código)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ilike', "%{$search}%")
                  ->orWhere('user_code', 'ilike', "%{$search}%")
                  ->orWhereHas('profile', function ($profileQuery) use ($search) {
                      $profileQuery->where('first_name', 'ilike', "%{$search}%")
                                   ->orWhere('last_name', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por email verificado
        if (isset($filters['emailVerified'])) {
            $query->where('email_verified', $filters['emailVerified']);
        }

        // Filtro por rol
        if (!empty($filters['role'])) {
            $query->whereHas('userRoles', function ($q) use ($filters) {
                $q->where('role_code', $filters['role'])
                  ->where('is_active', true);
            });
        }

        // Filtro por empresa
        if (!empty($filters['companyId'])) {
            $query->whereHas('userRoles', function ($q) use ($filters) {
                $q->where('company_id', $filters['companyId'])
                  ->where('is_active', true);
            });
        }

        // Filtro por actividad reciente (últimos 7 días)
        if (!empty($filters['recentActivity'])) {
            $query->where('last_activity_at', '>=', now()->subDays(7));
        }

        // Filtro por rango de fecha de creación
        if (!empty($filters['createdBetween'])) {
            if (!empty($filters['createdBetween']['from'])) {
                $query->where('created_at', '>=', $filters['createdBetween']['from']);
            }
            if (!empty($filters['createdBetween']['to'])) {
                $query->where('created_at', '<=', $filters['createdBetween']['to']);
            }
        }

        // Si es COMPANY_ADMIN, filtrar solo usuarios de su empresa
        if (!$this->isPlatformAdmin($authUser)) {
            $companyIds = $this->getUserCompanyIds($authUser);
            if (!empty($companyIds)) {
                $query->whereHas('userRoles', function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->where('is_active', true);
                });
            }
        }
    }

    /**
     * Aplica ordenamiento al query
     */
    private function applyOrdering($query, array $orderBy): void
    {
        if (empty($orderBy)) {
            // Ordenamiento por defecto
            $query->orderBy('created_at', 'desc');
            return;
        }

        foreach ($orderBy as $order) {
            $field = $order['field'] ?? 'CREATED_AT';
            $direction = strtolower($order['order'] ?? 'DESC');

            match($field) {
                'CREATED_AT' => $query->orderBy('created_at', $direction),
                'UPDATED_AT' => $query->orderBy('updated_at', $direction),
                'EMAIL' => $query->orderBy('email', $direction),
                'STATUS' => $query->orderBy('status', $direction),
                'LAST_LOGIN_AT' => $query->orderBy('last_login_at', $direction),
                'LAST_ACTIVITY_AT' => $query->orderBy('last_activity_at', $direction),
                default => $query->orderBy('created_at', $direction)
            };
        }
    }

    /**
     * Verifica si el usuario es platform admin
     */
    private function isPlatformAdmin($user): bool
    {
        return $user->userRoles()
            ->where('role_code', 'PLATFORM_ADMIN')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtiene los IDs de empresas del usuario
     */
    private function getUserCompanyIds($user): array
    {
        return $user->userRoles()
            ->where('is_active', true)
            ->whereNotNull('company_id')
            ->pluck('company_id')
            ->unique()
            ->toArray();
    }
}