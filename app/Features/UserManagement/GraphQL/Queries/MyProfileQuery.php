<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

class MyProfileQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real - retornar perfil del usuario autenticado
        return null;
    }
}