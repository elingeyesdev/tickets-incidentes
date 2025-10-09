<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\AuthenticationException as BaseAuthenticationException;

/**
 * Refresh Token Required Exception
 *
 * Thrown when a refresh token is required but not provided.
 */
class RefreshTokenRequiredException extends BaseAuthenticationException
{
    protected string $errorCode = 'REFRESH_TOKEN_REQUIRED';

    public function __construct(string $message = 'Refresh token required. Send it via X-Refresh-Token header or refresh_token cookie.')
    {
        parent::__construct($message);
    }
}
