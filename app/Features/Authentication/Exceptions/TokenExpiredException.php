<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\AuthenticationException as BaseAuthenticationException;

/**
 * Token Expired Exception
 *
 * Thrown when a token has expired and needs to be refreshed.
 */
class TokenExpiredException extends BaseAuthenticationException
{
    protected string $errorCode = 'TOKEN_EXPIRED';

    public function __construct(string $message = 'Token has expired')
    {
        parent::__construct($message);
    }

    public static function accessToken(): self
    {
        return new self('Access token has expired. Please refresh your token.');
    }

    public static function refreshToken(): self
    {
        return new self('Refresh token has expired. Please login again.');
    }
}
