<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Helpers\JWTHelper;
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

            // Eager load admin con profile para evitar N+1 en getters
            $company->load('admin.profile');

            // Calcular isFollowedByMe si el usuario está autenticado usando DataLoader
            if (JWTHelper::isAuthenticated()) {
                $user = JWTHelper::getAuthenticatedUser();

                // Usar DataLoader para evitar query individual
                $loader = app(\App\Features\CompanyManagement\GraphQL\DataLoaders\FollowedCompanyIdsByUserIdBatchLoader::class);
                $followedIds = $loader->load($user->id);

                $company->isFollowedByMe = in_array($company->id, $followedIds);
            } else {
                $company->isFollowedByMe = false;
            }

            return $company;

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error fetching company: ' . $e->getMessage());
        }
    }
}