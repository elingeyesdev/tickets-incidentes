<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

/**
 * Excepción de refresh token expirado
 *
 * Lanzada cuando el refresh token ha superado su tiempo de vida
 * (típicamente 30 días). El usuario debe iniciar sesión nuevamente.
 */
class RefreshTokenExpiredException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'REFRESH_TOKEN_EXPIRED';

    public function __construct(string $message = '')
    {
        $defaultMessage = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.';
        parent::__construct($message ?: $defaultMessage);
    }

    public static function expired(): self
    {
        return new self();
    }

    public static function withDuration(int $daysAgo): self
    {
        return new self(
            "Tu sesión expiró hace {$daysAgo} días. Por favor inicia sesión nuevamente."
        );
    }
}
