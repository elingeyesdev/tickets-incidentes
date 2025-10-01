<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class ConfirmPasswordResetMutation extends BaseMutation
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real de confirmación de reset
        return [
            'success' => false,
            'message' => 'Not implemented yet',
        ];
    }
}