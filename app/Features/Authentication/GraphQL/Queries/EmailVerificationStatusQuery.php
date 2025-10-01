<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Queries;

use App\Shared\GraphQL\Queries\BaseQuery;

/**
 * Query: emailVerificationStatus
 * Estado de verificación de email del usuario actual
 */
class EmailVerificationStatusQuery extends BaseQuery
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        return null;
    }
}