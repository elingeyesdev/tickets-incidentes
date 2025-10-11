<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Mutations\BaseMutation;

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
     */
    public function __invoke($root, array $args, $context = null): array
    {
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
