<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Log;

/**
 * ResendVerificationMutation
 *
 * Reenvía el email de verificación al usuario autenticado.
 * Requiere autenticación y tiene rate limiting (3 cada 5 minutos).
 *
 * @usage GraphQL
 * ```graphql
 * mutation ResendVerification {
 *   resendVerification {
 *     success
 *     message
 *     canResend
 *     resendAvailableAt
 *   }
 * }
 * ```
 */
class ResendVerificationMutation extends BaseMutation
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Reenviar email de verificación
     *
     * Requiere autenticación JWT (@jwt directive)
     * Rate limiting: 3 intentos cada 5 minutos (@rateLimit en schema)
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array EmailVerificationResult
     */
    public function __invoke($root, array $args, $context = null): array
    {
        try {
            // Obtener usuario autenticado (garantizado por @jwt directive)
            // JwtDirective agrega el usuario al contexto
            $user = $context->user;

            if (!$user) {
                throw new AuthenticationException('Authentication required');
            }

            // Verificar que el usuario no esté ya verificado
            if ($user->hasVerifiedEmail()) {
                return [
                    'success' => false,
                    'message' => 'El email ya está verificado',
                    'canResend' => false,
                    'resendAvailableAt' => null,
                ];
            }

            // Reenviar verificación (AuthService dispara evento UserRegistered que envía email)
            $token = $this->authService->resendEmailVerification($user->id);

            Log::info('Email verification resent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => 'Email de verificación enviado correctamente. Revisa tu bandeja de entrada.',
                'canResend' => false, // No puede reenviar hasta que pase el rate limit
                'resendAvailableAt' => now()->addMinutes(5),
            ];

        } catch (AuthenticationException $e) {
            Log::warning('Failed to resend verification email', [
                'user_id' => $context->user?->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Retornar error como resultado (no throw - compatible con cliente)
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'canResend' => true,
                'resendAvailableAt' => now()->addMinute(),
            ];
        }
    }
}
