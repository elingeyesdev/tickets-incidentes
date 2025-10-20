<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\ProfileService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;

/**
 * Update My Profile Mutation V10.1
 *
 * Retorna ProfileUpdatePayload (SOLO perfil actualizado)
 * NO retorna User completo (sin roleContexts, tickets, etc.)
 */
class UpdateMyProfileMutation extends BaseMutation
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    /**
     * @param mixed $root
     * @param array{input: array{firstName?: string, lastName?: string, phoneNumber?: string, avatarUrl?: string}} $args
     * @param mixed|null $context
     * @return array{userId: string, profile: \App\Features\UserManagement\Models\UserProfile, updatedAt: string}
     */
    public function __invoke($root, array $args, $context = null): array
    {
        $user = JWTHelper::getAuthenticatedUser();

        // Actualizar solo el perfil
        $profile = $this->profileService->updateProfile($user->id, $args['input']);

        // Touch user para actualizar updated_at
        $user->touch();

        // ✅ Retornar SOLO lo relevante (ProfileUpdatePayload)
        return [
            'userId' => $user->id,
            'profile' => $profile->fresh(),
            'updatedAt' => $user->fresh()->updated_at,
        ];
    }
}
