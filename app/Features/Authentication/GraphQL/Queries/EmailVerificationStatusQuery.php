<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Exceptions\AuthenticationException;

/**
 * Query: emailVerificationStatus
 *
 * Obtiene el estado de verificación de email del usuario autenticado.
 *
 * @usage GraphQL
 * ```graphql
 * query EmailVerificationStatus {
 *   emailVerificationStatus {
 *     isVerified
 *     email
 *     verificationSentAt
 *     canResend
 *     resendAvailableAt
 *     attemptsRemaining
 *   }
 * }
 * ```
 */
class EmailVerificationStatusQuery extends BaseQuery
{
    /**
     * Constructor con dependency injection
     */
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Obtener estado de verificación
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array EmailVerificationStatus
     * @throws AuthenticationException
     */
    public function __invoke($root, array $args, $context = null): array
    {
        // Obtener usuario autenticado (garantizado por @jwt directive)
        // JwtDirective agrega el usuario al contexto
        $user = $context->user;

        if (!$user) {
            throw new AuthenticationException('Authentication required');
        }

        // Obtener status del servicio
        $status = $this->authService->getEmailVerificationStatus($user->id);

        // Calcular si puede reenviar (rate limit: 3 intentos cada 5 minutos)
        // Por simplicidad, siempre puede reenviar si no está verificado
        // El rate limit real lo maneja @rateLimit en el schema
        $canResend = !$status['is_verified'];
        $resendAvailableAt = $canResend ? now() : null;

        // Retornar en formato esperado por GraphQL
        return [
            'isVerified' => $status['is_verified'],
            'email' => $status['email'],
            'verificationSentAt' => $status['verified_at'] ?? $user->created_at,
            'canResend' => $canResend,
            'resendAvailableAt' => $resendAvailableAt,
            'attemptsRemaining' => $canResend ? 3 : 0, // Rate limit de 3 intentos (manejado por directive)
        ];
    }
}
