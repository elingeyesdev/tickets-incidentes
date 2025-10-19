<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Queries\BaseQuery;
use GraphQL\Error\Error;

class CompanyQuery extends BaseQuery
{
    public function __construct(
        private CompanyService $companyService,
        private CompanyFollowService $followService
    ) {}

    public function __invoke($root, array $args)
    {
        try {
            // Buscar empresa
            $company = $this->companyService->findById($args['id']);

            // Retornar null si no se encuentra (schema permite nullable)
            if (!$company) {
                return null;
            }

            // Los permisos son validados por la directiva @can en el schema
            // Calcular isFollowedByMe si el usuario está autenticado
            if (auth()->check()) {
                $user = auth()->user();
                $company->isFollowedByMe = $this->followService->isFollowing($user, $company);
            } else {
                $company->isFollowedByMe = false;
            }

            return $company;

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error al obtener empresa: ' . $e->getMessage());
        }
    }
}