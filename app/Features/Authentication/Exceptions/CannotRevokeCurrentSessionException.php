<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\ConflictException;

/**
 * Cannot Revoke Current Session Exception
 *
 * Thrown when trying to revoke the current active session.
 * User should use logout mutation instead.
 */
class CannotRevokeCurrentSessionException extends ConflictException
{
    protected string $errorCode = 'CANNOT_REVOKE_CURRENT_SESSION';

    public function __construct(string $message = 'Cannot revoke current session. Use logout mutation instead.')
    {
        parent::__construct($message);
    }
}
