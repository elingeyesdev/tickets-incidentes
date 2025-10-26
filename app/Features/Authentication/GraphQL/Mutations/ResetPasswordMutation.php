<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\PasswordResetService;
use App\Shared\GraphQL\Mutations\BaseMutation;

/**
 * Reset Password Mutation
 *
 * Solicita un reset de contraseña para el email proporcionado.
 * Siempre retorna true por seguridad (no revela si el email existe).
 *
 * Rate Limiting:
 * - 1 minuto entre resends
 * - Máximo 2 emails cada 3 horas
 */
class ResetPasswordMutation extends BaseMutation
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * @param mixed $root
     * @param array{email: string} $args
     * @param mixed $context
     * @return bool Siempre true por seguridad
     */
    public function __invoke($root, array $args, $context = null): bool
    {
        try {
            $email = strtolower(trim($args['email'] ?? ''));

            // Solicitar reset (retorna true siempre, lanza excepción si rate limit)
            return $this->passwordResetService->requestReset($email);
        } catch (\Exception $e) {
            \Log::error('Exception in ResetPasswordMutation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
