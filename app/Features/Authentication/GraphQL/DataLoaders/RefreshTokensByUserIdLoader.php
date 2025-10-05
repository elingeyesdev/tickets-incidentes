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
 *     return $context->dataLoader(RefreshTokensByUserIdLoader::class)
 *         ->load($context->user()->id);
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
            // TODO: Reemplazar con modelo real cuando esté disponible
            // Por ahora retornamos datos mock para testing

            // Una vez tengamos el modelo RefreshToken, usar:
            /*
            use App\Features\Authentication\Models\RefreshToken;

            $refreshTokens = RefreshToken::query()
                ->whereIn('user_id', $keys)
                ->where('expires_at', '>', now())
                ->orderBy('last_used_at', 'desc')
                ->get()
                ->groupBy('user_id');

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $refreshTokens->get($key, collect()));
            */

            // MOCK DATA (remover después)
            $mockSessions = collect($keys)->mapWithKeys(function ($userId) {
                // Simular 1-3 sesiones por usuario
                $numSessions = rand(1, 3);
                $sessions = collect();

                $deviceNames = [
                    'Chrome on Windows',
                    'Safari on iPhone',
                    'Firefox on Mac',
                    'Edge on Windows',
                    'Chrome on Android'
                ];

                for ($i = 0; $i < $numSessions; $i++) {
                    $sessions->push((object) [
                        'id' => \Illuminate\Support\Str::uuid()->toString(),
                        'session_id' => 'sess_' . \Illuminate\Support\Str::random(16),
                        'user_id' => $userId,
                        'device_name' => $deviceNames[$i % count($deviceNames)],
                        'ip_address' => '192.168.1.' . rand(100, 200),
                        'user_agent' => 'Mozilla/5.0...',
                        'last_used_at' => now()->subHours(rand(0, 48)),
                        'expires_at' => now()->addDays(30)->subHours(rand(0, 48)),
                        'is_current' => $i === 0,
                        'created_at' => now()->subDays(rand(1, 60)),
                    ]);
                }

                return [$userId => $sessions];
            });

            // Retornar en el mismo orden que los keys
            return collect($keys)->map(fn($key) => $mockSessions->get($key, collect()));
        };
    }
}