<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class UnfollowCompanyMutation extends BaseMutation
{
    public function __construct(
        private CompanyFollowService $followService,
        private CompanyService $companyService
    ) {}

    public function __invoke($root, array $args, $context = null)
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

            // Dejar de seguir empresa
            return $this->followService->unfollow($user, $company);

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage(), null, null, null, null, $e, [
                'code' => 'NOT_FOLLOWING'
            ]);
        } catch (\Exception $e) {
            throw new Error('Error al dejar de seguir empresa: ' . $e->getMessage());
        }
    }
}