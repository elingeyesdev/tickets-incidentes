<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class ResendVerificationMutation extends BaseMutation
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real de reenvío de verificación
        return [
            'success' => false,
            'message' => 'Not implemented yet',
            'sentAt' => now(),
            'expiresAt' => now()->addHour(),
        ];
    }
}