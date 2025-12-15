<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\NotFoundException;

/**
 * Session Not Found Exception
 *
 * Thrown when a session/refresh token cannot be found.
 */
class SessionNotFoundException extends NotFoundException
{
    protected string $errorCode = 'SESSION_NOT_FOUND';

    public function __construct(string $message = 'Session not found or already revoked')
    {
        parent::__construct($message);
    }

    public static function forUser(string $sessionId): self
    {
        return new self("Session '{$sessionId}' not found or does not belong to you.");
    }
}
