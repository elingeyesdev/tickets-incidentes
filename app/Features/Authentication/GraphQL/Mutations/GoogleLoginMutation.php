<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class GoogleLoginMutation extends BaseMutation
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real de login con Google
        return null;
    }
}