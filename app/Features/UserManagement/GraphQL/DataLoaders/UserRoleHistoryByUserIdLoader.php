<?php

namespace App\Features\UserManagement\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar historial COMPLETO de roles de usuarios (activos + inactivos)
 *
 * Evita N+1 queries al obtener roleHistory en User.
 * A diferencia de UserRolesByUserIdLoader (solo activos), este retorna TODOS los roles.
 *
 * @example
 * ```php
 * // En un resolver de User.roleHistory:
 * public function roleHistory($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(UserRoleHistoryByUserIdLoader::class)
 *         ->load($root->id);
 * }
 * ```
 */
class UserRoleHistoryByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a su historial completo de roles en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // TODO: Reemplazar con modelo real cuando esté disponible
            // Por ahora retornamos datos mock para testing

            // Una vez tengamos el modelo UserRole, usar:
            /*
            use App\Features\UserManagement\Models\UserRole;

            $userRoles = UserRole::query()
                ->whereIn('user_id', $keys)
                ->with(['role', 'company', 'assignedBy'])
                ->orderBy('assigned_at', 'desc')
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $userRoles->get($key, collect()));
            */

            // MOCK DATA (remover después)
            $mockRoleHistory = collect($keys)->mapWithKeys(function ($userId) {
                // Simular 1-3 roles en el historial
                $numRoles = rand(1, 3);
                $roles = collect();

                $availableRoles = [
                    ['code' => 'USER', 'name' => 'Cliente', 'requiresCompany' => false],
                    ['code' => 'AGENT', 'name' => 'Agente de Soporte', 'requiresCompany' => true],
                    ['code' => 'COMPANY_ADMIN', 'name' => 'Administrador de Empresa', 'requiresCompany' => true],
                ];

                for ($i = 0; $i < $numRoles; $i++) {
                    $roleInfo = $availableRoles[$i % count($availableRoles)];
                    $isActive = $i === 0; // Solo el primero está activo

                    $role = (object) [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'user_id' => $userId,
                        'role_code' => $roleInfo['code'],
                        'role_name' => $roleInfo['name'],
                        'role_description' => 'Descripción del rol ' . $roleInfo['name'],
                        'requires_company' => $roleInfo['requiresCompany'],
                        'is_active' => $isActive,
                        'assigned_at' => now()->subDays(rand(30, 365)),
                        'revoked_at' => $isActive ? null : now()->subDays(rand(1, 30)),
                    ];

                    // Agregar company si el rol lo requiere
                    if ($roleInfo['requiresCompany']) {
                        $companyId = \Illuminate\Support\Str::uuid()->toString();
                        $role->company_id = $companyId;
                        $role->company = (object) [
                            'id' => $companyId,
                            'company_code' => 'CMP-2025-' . str_pad(rand(1, 100), 5, '0', STR_PAD_LEFT),
                            'name' => 'Empresa ' . substr($companyId, 0, 8),
                            'logo_url' => 'https://ui-avatars.com/api/?name=Company+' . $i,
                        ];
                    } else {
                        $role->company_id = null;
                        $role->company = null;
                    }

                    // Agregar assignedBy
                    $assignerId = \Illuminate\Support\Str::uuid()->toString();
                    $role->assigned_by_id = $assignerId;
                    $role->assigned_by = (object) [
                        'id' => $assignerId,
                        'display_name' => 'Admin Sistema',
                        'email' => 'admin@sistema.com',
                    ];

                    $roles->push($role);
                }

                return [$userId => $roles];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockRoleHistory->get($key, collect()));
        };
    }
}