<?php

namespace App\Features\Authentication\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar todas las sesiones (RefreshTokens) de un usuario
 *
 * Evita N+1 queries al obtener mySessions.
 * Retorna TODAS las sesiones activas de un usuario (multi-device).
 *
 * @example
 * ```php
 * // En un resolver de mySessions:
 * public function mySessions($root, array $args, GraphQLContext $context)
 * {
 *     $user = \App\Shared\Helpers\JWTHelper::getAuthenticatedUser();
 *     return $context->dataLoader(RefreshTokensByUserIdLoader::class)
 *         ->load($user->id);
 * }
 * ```
 */
class RefreshTokensByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a sus sesiones activas en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // Cargar refresh tokens activos por user_id
            $refreshTokens = \App\Features\Authentication\Models\RefreshToken::query()
                ->whereIn('user_id', $keys)
                ->where('expires_at', '>', now())
                ->whereNull('revoked_at')
                ->orderBy('last_used_at', 'desc')
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys (array de tokens por usuario)
            return collect($keys)->map(fn($key) => $refreshTokens->get($key, collect()));
        };
    }
}