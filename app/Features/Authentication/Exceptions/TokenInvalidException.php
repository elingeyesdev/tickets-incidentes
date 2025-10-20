<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\AuthenticationException as BaseAuthenticationException;

/**
 * Token Invalid Exception
 *
 * Thrown when a token (access or refresh) is invalid or revoked.
 * Used for both access tokens and refresh tokens.
 */
class TokenInvalidException extends BaseAuthenticationException
{
    protected string $errorCode = 'INVALID_TOKEN';

    public function __construct(string $message = 'Token inválido o ya revocado')
    {
        parent::__construct($message);
    }

    public static function accessToken(): self
    {
        return new self('Access token is invalid or has been revoked.');
    }

    public static function refreshToken(): self
    {
        return new self('Refresh token is invalid or has been revoked.');
    }

    public static function revoked(): self
    {
        return new self('Token inválido o ya revocado');
    }
}
