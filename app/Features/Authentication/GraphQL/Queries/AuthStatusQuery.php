<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * Query: authStatus
 * Retorna el estado de autenticación del usuario actual
 */
class AuthStatusQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        return null;
    }
}