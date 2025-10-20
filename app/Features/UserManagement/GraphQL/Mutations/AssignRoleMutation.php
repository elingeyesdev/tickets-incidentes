<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\RoleService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;

/**
 * Assign Role Mutation V10.1
 *
 * Asigna un rol a un usuario de manera INTELIGENTE:
 * - Si el rol existe inactivo: lo REACTIVA
 * - Si el rol no existe: lo CREA
 */
class AssignRoleMutation extends BaseMutation
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * @param mixed $root
     * @param array{input: array{userId: string, roleCode: string, companyId: string|null}} $args
     * @param mixed|null $context
     * @return array{success: bool, message: string, role: \App\Features\UserManagement\Models\UserRole}
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function __invoke($root, array $args, $context = null)
    {
        // Authorization: Require PLATFORM_ADMIN or COMPANY_ADMIN
        $user = JWTHelper::getAuthenticatedUser();

        if (!$user) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        if (!$user->hasRole('PLATFORM_ADMIN') && !$user->hasRole('COMPANY_ADMIN')) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Solo administradores pueden asignar roles'
            );
        }

        $input = $args['input'];

        $result = $this->roleService->assignRoleToUser(
            userId: $input['userId'],
            roleCode: $input['roleCode'],
            companyId: $input['companyId'] ?? null,
            assignedBy: JWTHelper::getUserId()
        );

        // Field resolvers handle loading relationships via DataLoaders
        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'role' => $result['role'],
        ];
    }
}
