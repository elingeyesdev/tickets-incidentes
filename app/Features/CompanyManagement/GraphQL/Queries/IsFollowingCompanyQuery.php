<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

class IsFollowingCompanyQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        // Verificar si el usuario autenticado sigue a la empresa
        return false;
    }
}
