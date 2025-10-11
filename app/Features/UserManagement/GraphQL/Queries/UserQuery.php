<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * User Query
 *
 * Retorna información completa de un usuario por ID.
 * Misma estructura que 'me' para consistencia.
 * El @can directive en el schema valida permisos automáticamente.
 */
class UserQuery extends BaseQuery
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke($root, array $args)
    {
        // El @can directive ya validó permisos, solo retornar el usuario
        return $this->userService->getUserById($args['id']);
    }
}