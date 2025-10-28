<?php

declare(strict_types=1);

namespace App\Features\Authentication\Http\Controllers;

use App\Features\Authentication\Http\Resources\MarkOnboardingCompletedResource;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * Onboarding Controller
 *
 * REST endpoints para onboarding de usuarios.
 * El onboarding es el flujo de primeros pasos después del registro.
 */
class OnboardingController
{
    /**
     * Mark onboarding as completed
     *
     * Marca el onboarding como completado para el usuario autenticado.
     * Establece el timestamp onboarding_completed_at.
     *
     * Email verification NO es prerequisito del onboarding.
     *
     * @authenticated true
     *
     * @response 200 {"success": true, "message": "...", "user": {...}}
     */
    #[OA\Post(
        path: '/api/auth/onboarding/completed',
        summary: 'Mark onboarding as completed',
        description: 'Mark onboarding process as completed for authenticated user',
        tags: ['Onboarding'],
        responses: [
            new OA\Response(response: 200, description: 'Onboarding marked as completed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function markCompleted(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                throw new AuthenticationException('User not authenticated');
            }

            // Replicar exactamente MarkOnboardingCompletedMutation

            // Si ya está completado, retornar éxito sin modificar
            if ($user->onboarding_completed_at !== null) {
                Log::info('Onboarding already completed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'onboarding_completed_at' => $user->onboarding_completed_at->toDateTimeString(),
                ]);

                return response()->json(new MarkOnboardingCompletedResource([
                    'success' => true,
                    'message' => 'El onboarding ya estaba completado',
                    'user' => $user->fresh(),
                ]), 200);
            }

            // Marcar onboarding como completado (establece timestamp)
            $user->onboarding_completed_at = now();
            $user->save();

            Log::info('Onboarding marked as completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'onboarding_completed_at' => $user->onboarding_completed_at->toDateTimeString(),
            ]);

            return response()->json(new MarkOnboardingCompletedResource([
                'success' => true,
                'message' => 'Onboarding completado exitosamente',
                'user' => $user->fresh(),
            ]), 200);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
