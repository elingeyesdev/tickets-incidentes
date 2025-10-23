<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Shared\GraphQL\Queries\BaseQuery;
use GraphQL\Error\Error;

class CompanyRequestsQuery extends BaseQuery
{
    public function __construct(
        private CompanyRequestService $requestService
    ) {}

    public function __invoke($root, array $args)
    {
        try {
            // Extraer filtros
            $status = $args['status'] ?? null;
            $first = $args['first'] ?? 15;

            // Obtener solicitudes (permisos manejados por directiva @auth en schema)
            return $this->requestService->getAll($status, $first);

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error fetching company requests: ' . $e->getMessage());
        }
    }
}