<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Queries\BaseQuery;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

/**
 * Me Query
 *
 * Retorna información completa del usuario autenticado.
 * Incluye: perfil, roles, empresas, estadísticas.
 */
class MeQuery extends BaseQuery
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke($root, array $args)
    {
        // Obtener usuario autenticado
        $authUser = Auth::user();

        if (!$authUser) {
            throw new Error('Usuario no autenticado');
        }

        // Delegar al service para cargar relaciones completas
        return $this->userService->getUserById($authUser->id);
    }
}