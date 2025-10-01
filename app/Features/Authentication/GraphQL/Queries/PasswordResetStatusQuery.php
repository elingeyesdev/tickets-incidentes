<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * Query: passwordResetStatus
 * Valida el estado de un token de reset de contraseña
 */
class PasswordResetStatusQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        return [
            'isValid' => false,
            'canReset' => false,
            'attemptsRemaining' => 3,
        ];
    }
}