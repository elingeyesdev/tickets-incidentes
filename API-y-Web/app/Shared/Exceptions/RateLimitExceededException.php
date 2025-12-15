<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de límite de tasa excedido (429)
 *
 * Lanzada cuando el usuario ha superado el límite de peticiones
 * permitidas en un periodo de tiempo.
 */
class RateLimitExceededException extends HelpdeskException
{
    protected string $category = 'rate_limit';
    protected string $errorCode = 'RATE_LIMIT_EXCEEDED';

    protected int $retryAfter; // Segundos hasta que puede reintentar
    protected int $limit;      // Límite de peticiones
    protected int $window;     // Ventana de tiempo en segundos

    public function __construct(
        string $message,
        int $retryAfter = 60,
        int $limit = 0,
        int $window = 60
    ) {
        parent::__construct($message);
        $this->retryAfter = $retryAfter;
        $this->limit = $limit;
        $this->window = $window;
    }

    public static function tooManyAttempts(int $retryAfter = 60): self
    {
        return new self(
            'Too many attempts. Please wait a moment before retrying.',
            $retryAfter
        );
    }

    public static function loginAttempts(int $retryAfter = 900): self
    {
        return new self(
            'Too many login attempts. Please wait 15 minutes.',
            $retryAfter,
            5,
            900
        );
    }

    public static function custom(string $action, int $limit, int $windowSeconds, int $retryAfter): self
    {
        // Round up window (119 seconds = 2 minutes, not 1)
        $windowMinutes = ceil($windowSeconds / 60);
        return new self(
            "Too many {$action} requests. Maximum {$limit} per {$windowMinutes} minutes. Try again in {$retryAfter} seconds.",
            $retryAfter,
            $limit,
            $windowSeconds
        );
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getWindow(): int
    {
        return $this->window;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'extensions' => [
                'code' => $this->getErrorCode(),
                'category' => $this->getCategory(),
                'retryAfter' => $this->retryAfter,
                'limit' => $this->limit > 0 ? $this->limit : null,
                'window' => $this->window > 0 ? $this->window : null,
            ],
        ];
    }
}
