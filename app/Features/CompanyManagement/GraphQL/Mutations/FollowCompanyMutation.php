<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class FollowCompanyMutation extends BaseMutation
{
    public function __invoke($root, array $args, $context = null)
    {
        // TODO: Implementar lógica real
        // Seguir una empresa
        return [
            'success' => true,
            'message' => 'Empresa seguida exitosamente',
            'company' => null,
            'followedAt' => now()->toIso8601String(),
        ];
    }
}