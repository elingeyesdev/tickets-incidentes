<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Queries;

use App\Features\UserManagement\Services\ProfileService;
use App\Shared\GraphQL\Queries\BaseQuery;
use GraphQL\Error\Error;
use Illuminate\Support\Facades\Auth;

/**
 * My Profile Query
 *
 * Retorna el perfil completo del usuario autenticado.
 * Solo información del perfil (sin User completo).
 */
class MyProfileQuery extends BaseQuery
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function __invoke($root, array $args)
    {
        // Obtener usuario autenticado
        $authUser = Auth::user();

        if (!$authUser) {
            throw new Error('Usuario no autenticado');
        }

        // Delegar al service
        return $this->profileService->getProfileByUserId($authUser->id);
    }
}