<?php

namespace App\Features\CompanyManagement\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

/**
 * DataLoader para cargar empresas seguidas por un usuario
 *
 * Evita N+1 queries al obtener myFollowedCompanies.
 * Retorna CompanyFollowInfo completo con estadísticas personalizadas.
 *
 * @example
 * ```php
 * // En un resolver de myFollowedCompanies:
 * public function myFollowedCompanies($root, array $args, GraphQLContext $context)
 * {
 *     return $context->dataLoader(FollowedCompaniesByUserIdLoader::class)
 *         ->load($context->user()->id);
 * }
 * ```
 */
class FollowedCompaniesByUserIdLoader extends BatchLoader
{
    /**
     * Resuelve múltiples user_ids a sus empresas seguidas en una sola query
     *
     * @param array<string> $keys Array de UUIDs de usuarios
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            use App\Features\CompanyManagement\Models\CompanyFollower;

            // Cargar follows con companies relacionadas
            $follows = CompanyFollower::query()
                ->whereIn('user_id', $keys)
                ->with('company')
                ->orderBy('followed_at', 'desc')
                ->get()
                ->groupBy('user_id');

            // Para cada follow, agregar estadísticas del usuario
            $followsWithStats = $follows->map(function ($userFollows) {
                return $userFollows->map(function ($follow) {
                    // TODO: Implementar cuando TicketManagement esté listo
                    /*
                    $myTicketsCount = \App\Features\TicketManagement\Models\Ticket::query()
                        ->where('author_id', $follow->user_id)
                        ->where('company_id', $follow->company_id)
                        ->count();

                    $lastTicket = \App\Features\TicketManagement\Models\Ticket::query()
                        ->where('author_id', $follow->user_id)
                        ->where('company_id', $follow->company_id)
                        ->latest()
                        ->first();

                    $follow->my_tickets_count = $myTicketsCount;
                    $follow->last_ticket_created_at = $lastTicket?->created_at;
                    */

                    // Valores temporales hasta que TicketManagement esté implementado
                    $follow->my_tickets_count = 0;
                    $follow->last_ticket_created_at = null;
                    $follow->has_unread_announcements = false; // TODO: implementar cuando Announcements esté listo

                    return $follow;
                });
            });

            // Retornar en el mismo orden que las claves
            return collect($keys)->map(fn($key) => $followsWithStats->get($key, collect()));
        };
    }
}