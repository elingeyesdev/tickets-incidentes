<?php declare(strict_types=1);

namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\UserRole;
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;

/**
 * BatchLoader para cargar roles de usuarios Y transformarlos a RoleContext
 *
 * Implementa el patrón de Lighthouse 6 usando GraphQL\Deferred
 * para prevenir N+1 queries al cargar roles de múltiples usuarios.
 *
 * A diferencia de UserRolesBatchLoader, este devuelve directamente
 * el array de RoleContext transformado, listo para GraphQL.
 *
 * @see \Nuwave\Lighthouse\Execution\BatchLoader\RelationBatchLoader
 */
class UserRoleContextsBatchLoader
{
    /**
     * Map from user_id to User model instances that need roles loading
     *
     * @var array<string, \App\Features\UserManagement\Models\User>
     */
    protected array $users = [];

    /**
     * Map from user_id to transformed RoleContext arrays
     *
     * @var array<string, array>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading roles for a user
     *
     * Returns a Deferred that resolves to an array of RoleContext when executed.
     *
     * @param \App\Features\UserManagement\Models\User $user
     * @return \GraphQL\Deferred
     */
    public function load(Model $user): Deferred
    {
        $userId = $user->id;
        $this->users[$userId] = $user;

        return new Deferred(function () use ($userId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$userId] ?? [];
        });
    }

    /**
     * Resolve all queued roles in a single batch query AND transform to RoleContext
     */
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);

        // Batch load all active roles with relationships in one query
        $userRoles = UserRole::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with(['role', 'company']) // Eager load role AND company
            ->get()
            ->groupBy('user_id');

        // Transform and map results back to user IDs
        foreach ($userIds as $userId) {
            $rolesForUser = $userRoles->get($userId, collect());

            // Transform to RoleContext format
            $this->results[$userId] = $rolesForUser->map(function ($userRole) {
                $roleCode = strtoupper($userRole->role_code);

                // Mapear dashboard paths según rol
                $dashboardPaths = [
                    'USER' => '/tickets',
                    'AGENT' => '/agent/dashboard',
                    'COMPANY_ADMIN' => '/empresa/dashboard',
                    'PLATFORM_ADMIN' => '/platform/dashboard',
                ];

                // Mapear nombres legibles de roles
                $roleNames = [
                    'USER' => 'Cliente',
                    'AGENT' => 'Agente de Soporte',
                    'COMPANY_ADMIN' => 'Administrador de Empresa',
                    'PLATFORM_ADMIN' => 'Administrador de Plataforma',
                ];

                $roleContext = [
                    'roleCode' => $roleCode,
                    'roleName' => $roleNames[$roleCode] ?? ($userRole->role->role_name ?? 'Unknown'),
                    'dashboardPath' => $dashboardPaths[$roleCode] ?? '/dashboard',
                ];

                // Agregar company solo si el rol requiere empresa
                // USER y PLATFORM_ADMIN: company es null
                // AGENT y COMPANY_ADMIN: company tiene datos
                if ($userRole->company) {
                    $roleContext['company'] = [
                        'id' => $userRole->company->id,
                        'companyCode' => $userRole->company->company_code,
                        'name' => $userRole->company->name,
                        'logoUrl' => $userRole->company->logo_url,
                    ];
                } else {
                    $roleContext['company'] = null;
                }

                return $roleContext;
            })->values()->toArray();
        }

        $this->hasResolved = true;
    }
}
