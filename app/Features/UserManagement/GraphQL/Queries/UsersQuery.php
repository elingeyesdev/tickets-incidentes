<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

class UsersQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real - retornar lista paginada de usuarios
        return [
            'data' => [],
            'paginatorInfo' => [
                'total' => 0,
                'perPage' => $args['first'] ?? 15,
                'currentPage' => $args['page'] ?? 1,
                'lastPage' => 1,
                'hasMorePages' => false,
            ],
        ];
    }
}