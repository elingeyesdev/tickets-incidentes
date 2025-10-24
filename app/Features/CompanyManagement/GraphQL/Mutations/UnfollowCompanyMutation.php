<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Errors\GraphQLErrorWithExtensions;
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
                throw new GraphQLErrorWithExtensions('User not authenticated', [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar empresa
            $company = $this->companyService->findById($args['companyId']);

            if (!$company) {
                throw new GraphQLErrorWithExtensions('Company not found', [
                    'code' => 'COMPANY_NOT_FOUND',
                    'companyId' => $args['companyId']
                ]);
            }

            // Dejar de seguir empresa
            return $this->followService->unfollow($user, $company);

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new GraphQLErrorWithExtensions($e->getMessage(), [
                'code' => 'NOT_FOLLOWING'
            ], $e);
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}