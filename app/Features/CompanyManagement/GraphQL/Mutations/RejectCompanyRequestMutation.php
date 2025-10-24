<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Mutations;

use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class RejectCompanyRequestMutation extends BaseMutation
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
                throw new Error('User not authenticated', null, null, null, null, null, [
                    'code' => 'UNAUTHENTICATED'
                ]);
            }

            // Buscar solicitud
            $request = CompanyRequest::find($args['requestId']);

            if (!$request) {
                throw new Error('Request not found', null, null, null, null, null, [
                    'code' => 'REQUEST_NOT_FOUND',
                    'requestId' => $args['requestId']
                ]);
            }

            // Rechazar solicitud
            $this->requestService->reject($request, $reviewer, $args['reason']);

            return true;

        } catch (Error $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new Error($e->getMessage(), null, null, null, null, $e, [
                'code' => 'REQUEST_NOT_PENDING'
            ]);
        } catch (\Exception $e) {
            throw new Error($e->getMessage());
        }
    }
}