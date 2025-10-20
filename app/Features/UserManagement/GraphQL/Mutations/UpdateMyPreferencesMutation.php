<?php declare(strict_types=1);

namespace App\Features\UserManagement\GraphQL\Mutations;

use App\Features\UserManagement\Services\ProfileService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;

/**
 * Update My Preferences Mutation V10.1
 *
 * Retorna PreferencesUpdatePayload (SOLO preferencias actualizadas)
 * NO retorna User completo
 */
class UpdateMyPreferencesMutation extends BaseMutation
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    /**
     * @param mixed $root
     * @param array{input: array{theme?: string, language?: string, timezone?: string, pushWebNotifications?: bool, notificationsTickets?: bool}} $args
     * @param mixed|null $context
     * @return array{userId: string, preferences: array, updatedAt: string}
     */
    public function __invoke($root, array $args, $context = null): array
    {
        $user = JWTHelper::getAuthenticatedUser();

        // Actualizar solo preferencias
        $profile = $this->profileService->updatePreferences($user->id, $args['input']);

        // ✅ Retornar SOLO preferencias (PreferencesUpdatePayload)
        return [
            'userId' => $user->id,
            'preferences' => [
                'theme' => $profile->theme,
                'language' => $profile->language,
                'timezone' => $profile->timezone,
                'pushWebNotifications' => $profile->push_web_notifications,
                'notificationsTickets' => $profile->notifications_tickets,
                'updatedAt' => $profile->updated_at,
            ],
            'updatedAt' => $profile->updated_at,
        ];
    }
}
