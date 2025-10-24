<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Shared\GraphQL\Errors\GraphQLErrorWithExtensions;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class ApproveCompanyRequestMutation extends BaseMutation
{
    public function __construct(
        private CompanyRequestService $requestService
    ) {}

    public function __invoke($root, array $args, $context = null)
    {
        try {
            // Obtener revisor (usuario autenticado - permisos manejados por directiva @auth)
            $reviewer = JWTHelper::getAuthenticatedUser();

            if (!$reviewer) {
                throw new GraphQLErrorWithExtensions('User not authenticated', [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar solicitud
            $request = CompanyRequest::find($args['requestId']);

            if (!$request) {
                throw new GraphQLErrorWithExtensions('Request not found', [
                    'code' => 'REQUEST_NOT_FOUND',
                    'requestId' => $args['requestId']
                ]);
            }

            // Aprobar solicitud (crea empresa y asigna rol de admin)
            $company = $this->requestService->approve($request, $reviewer);

            return $company;

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}