<?php

namespace App\Shared\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;

/**
 * Excepción base del sistema Helpdesk
 *
 * Todas las excepciones custom deben heredar de esta clase.
 * Implementa ClientAware para ser segura en GraphQL.
 */
abstract class HelpdeskException extends Exception implements ClientAware
{
    /**
     * Categoría del error para logging/metrics
     */
    protected string $category = 'general';

    /**
     * Si el error es seguro para mostrar al cliente
     */
    protected bool $isClientSafe = true;

    /**
     * Código de error para el cliente
     */
    protected string $errorCode;

    /**
     * Constructor
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        // Si no se especificó errorCode, usar el nombre de la clase
        if (!isset($this->errorCode)) {
            $this->errorCode = class_basename(static::class);
        }
    }

    /**
     * Si es seguro mostrar el error al cliente (ClientAware)
     */
    public function isClientSafe(): bool
    {
        return $this->isClientSafe;
    }

    /**
     * Obtiene la categoría del error (ClientAware)
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Obtiene el código de error
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Convierte la excepción a array para respuesta GraphQL
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'extensions' => [
                'code' => $this->getErrorCode(),
                'category' => $this->getCategory(),
            ],
        ];
    }
}