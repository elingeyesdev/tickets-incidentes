<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;

/**
 * Activate User Mutation V10.1
 *
 * Retorna UserStatusPayload (SOLO userId y status)
 * NO retorna User completo
 */
class ActivateUserMutation extends BaseMutation
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * @param mixed $root
     * @param array{id: string} $args
     * @param mixed|null $context
     * @return array{userId: string, status: string, updatedAt: string}
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Authorization: Require PLATFORM_ADMIN only
        $authUser = JWTHelper::getAuthenticatedUser();

        if (!$authUser) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$authUser->hasRole('PLATFORM_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores de plataforma pueden activar usuarios'
            );
        }

        $user = $this->userService->activateUser($args['id']);

        // ✅ Retornar SOLO userId y status (UserStatusPayload)
        return [
            'userId' => $user->id,
            'status' => $user->status,
            'updatedAt' => $user->updated_at,
        ];
    }
}
