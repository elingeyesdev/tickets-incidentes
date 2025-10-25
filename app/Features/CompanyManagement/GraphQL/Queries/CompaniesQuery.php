<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Shared\GraphQL\Errors\GraphQLErrorWithExtensions;
use App\Shared\GraphQL\Queries\BaseQuery;
use App\Shared\Helpers\JWTHelper;
use GraphQL\Error\Error;

class CompaniesQuery extends BaseQuery
{
    public function __construct(
        private CompanyFollowService $followService
    ) {}

    public function __invoke($root, array $args)
    {
        try {
            // Extraer contexto y parámetros
            $context = $args['context'];
            $page = $args['page'] ?? 1;
            $first = $args['first'] ?? 20;
            $search = $args['search'] ?? null;
            $filters = $args['filters'] ?? [];

            // Validar autenticación para contexto EXPLORE
            if ($context === 'EXPLORE' && !JWTHelper::isAuthenticated()) {
                throw GraphQLErrorWithExtensions::unauthenticated();
            }

            // Construir consulta base
            $query = Company::query();

            // Aplicar selección de campos basada en contexto
            // Note: Select all needed fields for each context
            switch ($context) {
                case 'MINIMAL':
                    // Only basic fields for minimal context
                    // No select() - let Eloquent get all fields for now
                    break;

                case 'EXPLORE':
                    // All fields for explore context (description, industry are nullable/not in DB)
                    // No select() - let Eloquent get all fields for now
                    break;

                case 'MANAGEMENT':
                case 'ANALYTICS':
                    // Todos los campos (por defecto) + necesitamos admin relation
                    $query->with('admin.profile');
                    break;

                default:
                    throw new Error("Invalid context: {$context}");
            }

            // Aplicar filtros
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['industry'])) {
                // Nota: el campo industria no existe en la BD, omitir por ahora
                // TODO: Agregar campo industria a la tabla companies
            }

            if (isset($filters['country'])) {
                $query->where('contact_country', $filters['country']);
            }

            if (isset($filters['hasActiveTickets'])) {
                // TODO: Implementar cuando la funcionalidad de tickets esté lista
            }

            if (isset($filters['followedByMe']) && $filters['followedByMe'] === true && JWTHelper::isAuthenticated()) {
                $user = JWTHelper::getAuthenticatedUser();
                $followedIds = $this->followService->getFollowedCompanies($user)->pluck('id')->toArray();
                $query->whereIn('id', $followedIds);
            }

            // Aplicar búsqueda (texto completo en name, legal_name)
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('legal_name', 'ILIKE', "%{$search}%");
                });
            }

            // Obtener conteo total antes de paginación
            $total = $query->count();

            // Aplicar paginación - obtener first + 1 para saber si hay siguiente página
            $offset = ($page - 1) * $first;
            $companies = $query->offset($offset)->limit($first + 1)->get();

            // Calcular hasNextPage antes de truncar
            $hasNextPage = $companies->count() > $first;

            // Truncar a $first items si hay más
            if ($hasNextPage) {
                $companies = $companies->take($first);
            }

            // Retornar tipo unión basado en contexto
            return [
                '__typename' => $this->getTypeName($context),
                'items' => $companies,
                'totalCount' => $total,
                'hasNextPage' => $hasNextPage,
            ];

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Error fetching companies: ' . $e->getMessage());
        }
    }

    /**
     * Obtener nombre de tipo GraphQL basado en contexto
     */
    private function getTypeName(string $context): string
    {
        return match($context) {
            'MINIMAL' => 'CompanyMinimalList',
            'EXPLORE' => 'CompanyExploreList',
            'MANAGEMENT', 'ANALYTICS' => 'CompanyFullList',
            default => throw new Error("Invalid context: {$context}"),
        };
    }
}