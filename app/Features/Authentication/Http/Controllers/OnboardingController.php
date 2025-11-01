<?php declare(strict_types=1);

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
     * @response 200 {"success": true, "message": "...", "user": {...}}
     */
    #[OA\Post(
        path: '/api/auth/onboarding/completed',
        summary: 'Mark onboarding as completed',
        description: 'Marks the onboarding process as completed for the authenticated user. Sets the onboarding_completed_at timestamp. If onboarding is already completed, returns success without modifications. Email verification is NOT a prerequisite for completing onboarding.',
        security: [['bearerAuth' => []]],
        tags: ['Onboarding'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Onboarding successfully marked as completed',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['success', 'message', 'user'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Onboarding completado exitosamente'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            required: ['id', 'userCode', 'email', 'emailVerified', 'onboardingCompleted', 'status', 'displayName'],
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d5e8e42-3f1c-4e8d-a8c4-5e3f1c4e8d9a'),
                                new OA\Property(property: 'userCode', type: 'string', example: 'USR-20251101-001'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                                new OA\Property(property: 'emailVerified', type: 'boolean', example: false),
                                new OA\Property(property: 'onboardingCompleted', type: 'boolean', example: true),
                                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE', 'SUSPENDED'], example: 'ACTIVE'),
                                new OA\Property(property: 'displayName', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'avatarUrl', type: 'string', format: 'url', nullable: true, example: 'https://example.com/avatars/john-doe.jpg'),
                                new OA\Property(property: 'theme', type: 'string', enum: ['light', 'dark'], example: 'light'),
                                new OA\Property(property: 'language', type: 'string', example: 'es'),
                                new OA\Property(
                                    property: 'roleContexts',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        required: ['roleCode', 'roleName', 'dashboardPath', 'company'],
                                        properties: [
                                            new OA\Property(property: 'roleCode', type: 'string', enum: ['USER', 'AGENT', 'COMPANY_ADMIN', 'PLATFORM_ADMIN'], example: 'USER'),
                                            new OA\Property(property: 'roleName', type: 'string', example: 'Cliente'),
                                            new OA\Property(property: 'dashboardPath', type: 'string', example: '/tickets'),
                                            new OA\Property(
                                                property: 'company',
                                                type: 'object',
                                                nullable: true,
                                                required: ['id', 'companyCode', 'name'],
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '8d4e7e31-2f0b-3d7c-b9c3-4e2f0b3d7c8d'),
                                                    new OA\Property(property: 'companyCode', type: 'string', example: 'COMP-20251101-001'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                                ]
                                            ),
                                        ]
                                    ),
                                    example: [
                                        [
                                            'roleCode' => 'USER',
                                            'roleName' => 'Cliente',
                                            'dashboardPath' => '/tickets',
                                            'company' => null,
                                        ],
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'User not authenticated - Missing or invalid bearer token',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function markCompleted(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
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
