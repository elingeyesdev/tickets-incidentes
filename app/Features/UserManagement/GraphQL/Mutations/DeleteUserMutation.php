<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\UserService;
use App\Shared\GraphQL\Mutations\BaseMutation;

/**
 * Delete User Mutation V10.1
 *
 * Elimina lógicamente un usuario (soft delete).
 * Establece status como DELETED y anonimiza datos sensibles según GDPR.
 * Solo accesible por PLATFORM_ADMIN.
 */
class DeleteUserMutation extends BaseMutation
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * @param mixed $root
     * @param array{id: string, reason?: string} $args
     * @param mixed|null $context
     * @return bool
     */
    public function __invoke($root, array $args, $context = null): bool
    {
        return $this->userService->deleteUser($args['id']);

        // Nota: El reason se registra automáticamente por la directiva @audit en el schema
    }
}