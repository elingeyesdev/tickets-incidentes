<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Features\CompanyManagement\Services\CompanyService;
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
                throw new Error('User not authenticated', null, null, null, null, null, [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar empresa
            $company = $this->companyService->findById($args['companyId']);

            if (!$company) {
                throw new Error('Company not found', null, null, null, null, null, [
                    'code' => 'COMPANY_NOT_FOUND',
                    'companyId' => $args['companyId']
                ]);
            }

            // Validar que la empresa esté activa
            if ($company->status !== 'active') {
                throw new Error('Cannot follow a suspended company', null, null, null, null, null, [
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
                throw new Error($message, null, null, null, null, $e, [
                    'code' => 'ALREADY_FOLLOWING',
                    'companyId' => $args['companyId']
                ]);
            }

            if (str_contains($message, 'maximum number')) {
                throw new Error($message, null, null, null, null, $e, [
                    'code' => 'MAX_FOLLOWS_EXCEEDED',
                    'currentFollows' => 50,
                    'maxAllowed' => 50
                ]);
            }

            throw new Error($message, null, null, null, null, $e, [
                'code' => 'VALIDATION_ERROR'
            ]);
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}