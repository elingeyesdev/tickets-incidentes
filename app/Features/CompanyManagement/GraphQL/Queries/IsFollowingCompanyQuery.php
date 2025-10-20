<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class IsFollowingCompanyQuery extends BaseQuery
{
    public function __construct(
        private CompanyFollowService $followService,
        private CompanyService $companyService
    ) {}

    public function __invoke($root, array $args)
    {
        try {
            // Obtener usuario autenticado
            $user = JWTHelper::getAuthenticatedUser();

            if (!$user) {
                throw new Error('Usuario no autenticado', null, null, null, null, null, [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar empresa
            $company = $this->companyService->findById($args['companyId']);

            if (!$company) {
                throw new Error('Empresa no encontrada', null, null, null, null, null, [
                    'code' => 'COMPANY_NOT_FOUND',
                    'companyId' => $args['companyId']
                ]);
            }

            // Verificar si está siguiendo
            return $this->followService->isFollowing($user, $company);

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error al verificar seguimiento: ' . $e->getMessage());
        }
    }
}
