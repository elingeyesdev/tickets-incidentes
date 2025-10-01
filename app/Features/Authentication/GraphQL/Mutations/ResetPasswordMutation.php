<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class ResetPasswordMutation extends BaseMutation
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real de reset password
        return true;
    }
}