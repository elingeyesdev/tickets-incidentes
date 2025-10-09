<?php

namespace App\Features\Authentication\GraphQL\Errors;

use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\Authentication\Exceptions\TokenExpiredException;
use App\Features\Authentication\Exceptions\RefreshTokenRequiredException;
use App\Features\Authentication\Exceptions\SessionNotFoundException;
use App\Features\Authentication\Exceptions\CannotRevokeCurrentSessionException;
use App\Shared\GraphQL\Errors\BaseErrorHandler;
use App\Shared\GraphQL\Errors\ErrorCodeRegistry;

/**
 * Token Error Handler
 *
 * Professional error handler for Authentication token-related exceptions.
 * Extends the global BaseErrorHandler to leverage the DEV/PROD error system.
 *
 * Handles:
 * - Token validation errors
 * - Token expiration
 * - Refresh token requirements
 * - Session management errors
 *
 * @see BaseErrorHandler - Provides DEV/PROD differentiation
 * @see ErrorCodeRegistry - Centralized error codes
 */
class TokenErrorHandler extends BaseErrorHandler
{
    /**
     * Determine if this handler should handle the exception
     *
     * @param \Throwable $exception
     * @return bool
     */
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof TokenInvalidException
            || $exception instanceof TokenExpiredException
            || $exception instanceof RefreshTokenRequiredException
            || $exception instanceof SessionNotFoundException
            || $exception instanceof CannotRevokeCurrentSessionException;
    }

    /**
     * Format the error response
     *
     * No additional formatting needed - BaseErrorHandler handles everything
     *
     * @param array $result Base error result from Lighthouse
     * @param \Throwable $exception
     * @return array Formatted error
     */
    protected function formatError(array $result, \Throwable $exception): array
    {
        return $result;
    }

    /**
     * Get the appropriate error code for the exception
     *
     * @param \Throwable $exception
     * @return string Error code from registry
     */
    protected function getErrorCode(\Throwable $exception): string
    {
        return match(true) {
            $exception instanceof TokenInvalidException => ErrorCodeRegistry::INVALID_TOKEN,
            $exception instanceof TokenExpiredException => ErrorCodeRegistry::TOKEN_EXPIRED,
            $exception instanceof RefreshTokenRequiredException => ErrorCodeRegistry::REFRESH_TOKEN_REQUIRED,
            $exception instanceof SessionNotFoundException => ErrorCodeRegistry::SESSION_NOT_FOUND,
            $exception instanceof CannotRevokeCurrentSessionException => ErrorCodeRegistry::CANNOT_REVOKE_CURRENT_SESSION,
            default => ErrorCodeRegistry::UNAUTHENTICATED,
        };
    }

    /**
     * Get production-safe error message
     *
     * @param \Throwable $exception
     * @return string User-friendly message
     */
    protected function getProductionMessage(\Throwable $exception): string
    {
        return match(true) {
            $exception instanceof TokenInvalidException => 'Token inválido o ya revocado',
            $exception instanceof TokenExpiredException => 'Your session has expired. Please login again.',
            $exception instanceof RefreshTokenRequiredException => 'Refresh token is required to perform this operation.',
            $exception instanceof SessionNotFoundException => 'Session not found or already revoked.',
            $exception instanceof CannotRevokeCurrentSessionException => 'Cannot revoke current session. Use logout instead.',
            default => 'Authentication error occurred.',
        };
    }

    /**
     * Get development error message
     *
     * Uses the original exception message for detailed debugging
     *
     * @param \Throwable $exception
     * @return string
     */
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return $exception->getMessage();
    }

    /**
     * Get service name for logging context
     *
     * @return string|null
     */
    protected function getServiceName(): ?string
    {
        return 'Authentication';
    }
}
