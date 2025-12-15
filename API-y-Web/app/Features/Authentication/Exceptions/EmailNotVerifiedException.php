<?php

namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\HelpdeskException;

/**
 * Excepción de email no verificado
 *
 * Lanzada cuando un usuario intenta acceder a funcionalidades
 * que requieren que su email esté verificado.
 */
class EmailNotVerifiedException extends HelpdeskException
{
    protected string $category = 'authentication';
    protected string $errorCode = 'EMAIL_NOT_VERIFIED';

    public function __construct(string $message = '')
    {
        $defaultMessage = 'Por favor verifica tu email antes de continuar. Revisa tu bandeja de entrada.';
        parent::__construct($message ?: $defaultMessage);
    }

    public static function mustVerify(): self
    {
        return new self();
    }

    public static function forAction(string $action): self
    {
        return new self("Debes verificar tu email para: {$action}");
    }

    public static function withResendOption(): self
    {
        return new self(
            'Email no verificado. Hemos enviado un nuevo enlace de verificación a tu correo.'
        );
    }
}
