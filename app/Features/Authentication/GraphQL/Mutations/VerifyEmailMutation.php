<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Log;

/**
 * VerifyEmailMutation
 *
 * Verifica el email del usuario usando un token de verificación.
 * Implementación profesional estándar (GitHub, Google, Twitter):
 * - Solo requiere el token (no userId)
 * - El token identifica al usuario automáticamente
 * - El usuario hace click en el link del email
 *
 * @usage GraphQL
 * ```graphql
 * mutation VerifyEmail($token: String!) {
 *   verifyEmail(token: $token) {
 *     success
 *     message
 *     canResend
 *     resendAvailableAt
 *   }
 * }
 * ```
 */
class VerifyEmailMutation extends BaseMutation
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Verificar email con token
     *
     * IMPORTANTE: Esta mutation NO requiere autenticación (@jwt)
     * El token en sí identifica al usuario (estándar industria)
     *
     * @param  mixed  $root
     * @param  array{token: string}  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array EmailVerificationResult
     */
    public function __invoke($root, array $args, $context = null): array
    {
        try {
            // Verificar email usando token (el servicio encuentra al usuario por el token)
            $user = $this->authService->verifyEmail($args['token']);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => '¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.',
                'canResend' => false,
                'resendAvailableAt' => null,
            ];

        } catch (AuthenticationException $e) {
            // Error de verificación (token inválido, expirado, email ya verificado)
            Log::warning('Email verification failed', [
                'token_preview' => substr($args['token'], 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            // Retornar error como resultado (no throw - compatible con cliente)
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'canResend' => true,
                'resendAvailableAt' => now(),
            ];
        }
    }
}
