<?php

namespace App\Features\Authentication\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar RefreshToken por session_id
 *
 * Evita N+1 queries al obtener información de la sesión activa.
 * Usado en authStatus.currentSession.
 *
 * @example
 * ```php
 * // En un resolver:
 * public function currentSession($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(RefreshTokenBySessionIdLoader::class)
 *         ->load($root->session_id);
 * }
 * ```
 */
class RefreshTokenBySessionIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples session_ids a sus refresh tokens en una sola query
     *
     * @param array<string> $keys Array de session IDs
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // TODO: Reemplazar con modelo real cuando esté disponible
            // Por ahora retornamos datos mock para testing

            // Una vez tengamos el modelo RefreshToken, usar:
            /*
            use App\Features\Authentication\Models\RefreshToken;

            $refreshTokens = RefreshToken::query()
                ->whereIn('session_id', $keys)
                ->where('expires_at', '>', now())
                ->get()
                ->keyBy('session_id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $refreshTokens->get($key));
            */

            // MOCK DATA (remover después)
            $mockTokens = collect($keys)->mapWithKeys(function ($sessionId) {
                return [
                    $sessionId => (object) [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'session_id' => $sessionId,
                        'device_name' => 'Chrome on Windows',
                        'ip_address' => '192.168.1.' . rand(100, 200),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'last_used_at' => now(),
                        'expires_at' => now()->addDays(30),
                        'is_current' => true,
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]
                ];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockTokens->get($key));
        };
    }
}