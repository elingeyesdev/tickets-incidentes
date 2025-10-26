<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Features\Authentication\Services\PasswordResetService;
use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * Query: passwordResetStatus
 * 
 * Valida el estado de un token de reset de contraseña
 * 
 * Retorna:
 * - isValid: bool - Token válido y no expirado
 * - canReset: bool - Usuario puede hacer reset
 * - email: string - Email enmascarado (privacidad)
 * - expiresAt: DateTime - Cuando expira el token
 */
class PasswordResetStatusQuery extends BaseQuery
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * @param mixed $root
     * @param array{token: string} $args
     * @return array{isValid: bool, canReset: bool, email: string|null, expiresAt: string|null}
     */
    public function __invoke($root, array $args)
    {
        $token = $args['token'] ?? null;

        if (!$token) {
            return [
                'isValid' => false,
                'canReset' => false,
                'email' => null,
                'expiresAt' => null,
                'attemptsRemaining' => 0,
            ];
        }

        // Validar token
        $status = $this->passwordResetService->validateResetToken($token);

        return [
            'isValid' => $status['is_valid'],
            'canReset' => $status['is_valid'],
            'email' => $status['email'],
            'expiresAt' => $status['expires_at'] ? \Carbon\Carbon::createFromTimestamp($status['expires_at'])->toIso8601String() : null,
            'attemptsRemaining' => $status['attempts_remaining'] ?? 0,
        ];
    }
}
