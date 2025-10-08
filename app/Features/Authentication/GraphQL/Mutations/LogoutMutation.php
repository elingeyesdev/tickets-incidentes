<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class LogoutMutation extends BaseMutation
{
    public function __invoke($root, array $args, $context = null)
    {
        // TODO: Implementar lógica real de logout
        return true;
    }
}