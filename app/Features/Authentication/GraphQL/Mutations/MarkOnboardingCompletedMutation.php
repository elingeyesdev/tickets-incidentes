<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Log;

/**
 * MarkOnboardingCompletedMutation
 *
 * Marca el onboarding como completado para el usuario autenticado.
 * Establece onboarding_completed_at timestamp en la base de datos.
 *
 * Este mutation se llama automáticamente después de completar el paso 2
 * del onboarding (ConfigurePreferences).
 *
 * Flujo completo de onboarding:
 * 1. CompleteProfile (first_name, last_name)
 * 2. ConfigurePreferences (theme, language)
 * 3. markOnboardingCompleted (establece timestamp) ← ESTE MUTATION
 *
 * IMPORTANTE: Email verification NO es prerequisito del onboarding.
 *
 * Requiere autenticación JWT (@jwt directive).
 *
 * @usage GraphQL
 * ```graphql
 * mutation MarkOnboardingCompleted {
 *   markOnboardingCompleted {
 *     success
 *     message
 *     user {
 *       id
 *       email
 *       onboardingComplete
 *     }
 *   }
 * }
 * ```
 */
class MarkOnboardingCompletedMutation extends BaseMutation
{
    /**
     * Mark onboarding as completed
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array{success: bool, message: string, user: \App\Features\UserManagement\Models\User|null}
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Obtener usuario autenticado (garantizado por @jwt directive)
        $user = $context->user;

        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }

        // Si ya está completado, retornar éxito sin modificar
        if ($user->onboarding_completed_at !== null) {
            Log::info('Onboarding already completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'onboarding_completed_at' => $user->onboarding_completed_at->toDateTimeString(),
            ]);

            return [
                'success' => true,
                'message' => 'El onboarding ya estaba completado',
                'user' => $user->fresh(),
            ];
        }

        // Marcar onboarding como completado (establece timestamp)
        $user->onboarding_completed_at = now();
        $user->save();

        Log::info('Onboarding marked as completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'onboarding_completed_at' => $user->onboarding_completed_at->toDateTimeString(),
        ]);

        return [
            'success' => true,
            'message' => 'Onboarding completado exitosamente',
            'user' => $user->fresh(),
        ];
    }
}
