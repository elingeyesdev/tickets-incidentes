<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Shared\GraphQL\Errors\GraphQLErrorWithExtensions;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class FollowCompanyMutation extends BaseMutation
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

            // Validar que la empresa esté activa
            if ($company->status !== 'active') {
                throw new GraphQLErrorWithExtensions('Cannot follow a suspended company', [
                    'code' => 'COMPANY_SUSPENDED',
                    'companyId' => $company->id
                ]);
            }

            // Seguir empresa
            $follow = $this->followService->follow($user, $company);

            // Retornar resultado
            return [
                'success' => true,
                'message' => "You are now following {$company->name}.",
                'company' => $company,
                'followedAt' => $follow->followed_at->toIso8601String(),
            ];

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            // Manejar errores específicos del servicio
            $message = $e->getMessage();

            if (str_contains($message, 'already following')) {
                throw new GraphQLErrorWithExtensions($message, [
                    'code' => 'ALREADY_FOLLOWING',
                    'companyId' => $args['companyId']
                ], $e);
            }

            if (str_contains($message, 'maximum number')) {
                throw new GraphQLErrorWithExtensions($message, [
                    'code' => 'MAX_FOLLOWS_EXCEEDED',
                    'currentFollows' => 50,
                    'maxAllowed' => 50
                ], $e);
            }

            throw new GraphQLErrorWithExtensions($message, [
                'code' => 'VALIDATION_ERROR'
            ], $e);
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}