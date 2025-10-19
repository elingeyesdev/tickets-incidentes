<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Shared\GraphQL\Queries\BaseQuery;
use GraphQL\Error\Error;

class MyFollowedCompaniesQuery extends BaseQuery
{
    public function __construct(
        private CompanyFollowService $followService
    ) {}

    public function __invoke($root, array $args)
    {
        try {
            // Obtener usuario autenticado
            $user = auth()->user();

            if (!$user) {
                throw new Error('Usuario no autenticado', null, null, null, null, null, [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Obtener empresas seguidas con metadatos
            return $this->followService->getFollowedWithMetadata($user);

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error al obtener empresas seguidas: ' . $e->getMessage());
        }
    }
}
