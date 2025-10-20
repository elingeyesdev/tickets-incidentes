<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;

/**
 * Suspend User Mutation V10.1
 *
 * Retorna UserStatusPayload (SOLO userId y status)
 * NO retorna User completo
 */
class SuspendUserMutation extends BaseMutation
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * @param mixed $root
     * @param array{id: string, reason?: string} $args
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
                'Solo administradores de plataforma pueden suspender usuarios'
            );
        }

        $user = $this->userService->suspendUser(
            userId: $args['id'],
            reason: $args['reason'] ?? null
        );

        // ✅ Retornar SOLO userId y status (UserStatusPayload)
        return [
            'userId' => $user->id,
            'status' => $user->status,
            'updatedAt' => $user->updated_at,
        ];
    }
}
