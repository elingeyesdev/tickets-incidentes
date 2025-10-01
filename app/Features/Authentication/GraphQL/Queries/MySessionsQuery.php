<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * Query: mySessions
 * Retorna lista de sesiones activas del usuario
 */
class MySessionsQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        return [];
    }
}