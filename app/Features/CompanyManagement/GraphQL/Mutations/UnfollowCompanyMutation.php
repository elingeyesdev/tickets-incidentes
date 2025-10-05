<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Shared\GraphQL\Mutations\BaseMutation;

class UnfollowCompanyMutation extends BaseMutation
{
    public function __invoke($root, array $args)
    {
        // TODO: Implementar lógica real
        // Dejar de seguir una empresa
        return true;
    }
}