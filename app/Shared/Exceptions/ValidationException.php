<?php

namespace App\Shared\Exceptions;

/**
 * Excepción de validación
 *
 * Lanzada cuando los datos de entrada no cumplen las reglas de validación.
 */
class ValidationException extends HelpdeskException
{
    protected string $category = 'validation';
    protected string $errorCode = 'VALIDATION_ERROR';
    protected array $errors = [];

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public static function withErrors(array $errors): self
    {
        return new self('Errores de validación.', $errors);
    }

    public static function fieldRequired(string $field): self
    {
        return new self("El campo '{$field}' es requerido.", [$field => ['required']]);
    }

    public static function invalidFormat(string $field, string $format): self
    {
        return new self(
            "El campo '{$field}' debe tener formato: {$format}",
            [$field => ['invalid_format']]
        );
    }

    public static function duplicateValue(string $field, string $value): self
    {
        return new self(
            "El valor '{$value}' ya existe para el campo '{$field}'.",
            [$field => ['duplicate']]
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'extensions' => [
                'code' => $this->getErrorCode(),
                'category' => $this->getCategory(),
                'errors' => $this->errors,
            ],
        ];
    }
}