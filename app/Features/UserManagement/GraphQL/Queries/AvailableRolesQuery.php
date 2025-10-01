<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

class AvailableRolesQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real - retornar roles disponibles
        return [];
    }
}