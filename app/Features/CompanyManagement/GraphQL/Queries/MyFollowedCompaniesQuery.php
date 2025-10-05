<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

class MyFollowedCompaniesQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        // Retornar empresas que el usuario autenticado está siguiendo
        return [];
    }
}
