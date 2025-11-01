<?php declare(strict_types=1);

namespace App\Shared\Errors;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;

/**
 * Error with Extensions Support
 *
 * Custom Error class that properly preserves extensions (error codes and metadata).
 * Used by REST API services to provide structured error responses.
 *
 * Problem:
 * - GraphQL\Error\Error constructor doesn't preserve extensions array parameter
 * - Responses need to access $errors[0]['extensions']['code']
 *
 * Solution:
 * - Implement ClientAware interface to return extensions
 * - Store extensions in constructor and return via getExtensions()
 *
 * Usage:
 * ```php
 * throw new ErrorWithExtensions(
 *     'Company not found',
 *     ['code' => 'COMPANY_NOT_FOUND', 'companyId' => $id]
 * );
 * ```
 */
class ErrorWithExtensions extends Error implements ClientAware
{
    private array $customExtensions = [];

    /**
     * Create error with extensions
     *
     * @param string $message Error message
     * @param array $extensions Extensions (code, metadata, etc.)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        array $extensions = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            null,      // nodes
            null,      // source
            [],        // positions
            null,      // path
            $previous  // previous
        );

        $this->customExtensions = $extensions;
    }

    /**
     * Return true to expose error message to client
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Return extensions for response
     */
    public function getExtensions(): array
    {
        return $this->customExtensions;
    }

    /**
     * Create validation error with code
     *
     * @param string $message Error message
     * @param string $code Error code (e.g., 'ALREADY_FOLLOWING')
     * @param array $additionalData Additional context data
     * @return self
     */
    public static function validation(
        string $message,
        string $code,
        array $additionalData = []
    ): self {
        return new self($message, array_merge(
            ['code' => $code],
            $additionalData
        ));
    }

    /**
     * Create unauthenticated error
     *
     * @param string $message Error message
     * @return self
     */
    public static function unauthenticated(string $message = 'Unauthenticated'): self
    {
        return new self($message, ['code' => 'UNAUTHENTICATED']);
    }

    /**
     * Create unauthorized error
     *
     * @param string $message Error message
     * @return self
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, ['code' => 'UNAUTHORIZED']);
    }

    /**
     * Create not found error
     *
     * @param string $message Error message
     * @param string $code Error code (e.g., 'COMPANY_NOT_FOUND')
     * @param array $additionalData Additional context data
     * @return self
     */
    public static function notFound(
        string $message,
        string $code,
        array $additionalData = []
    ): self {
        return new self($message, array_merge(
            ['code' => $code],
            $additionalData
        ));
    }
}
