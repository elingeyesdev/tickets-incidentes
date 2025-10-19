<?php declare(strict_types=1);

namespace App\Features\CompanyManagement\GraphQL\Queries;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Services\CompanyFollowService;
use App\Shared\GraphQL\Queries\BaseQuery;
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

            // Construir consulta base
            $query = Company::query();

            // Aplicar selección de campos basada en contexto
            switch ($context) {
                case 'MINIMAL':
                    $query->select(['id', 'company_code', 'name', 'logo_url']);
                    break;

                case 'EXPLORE':
                    $query->select([
                        'id', 'company_code', 'name', 'logo_url',
                        'support_email', // campo descripción (usar support_email como respaldo)
                        'website', // campo industria (marcador de posición)
                        'contact_city', 'contact_country',
                        'primary_color'
                    ]);
                    break;

                case 'MANAGEMENT':
                case 'ANALYTICS':
                    // Todos los campos (por defecto)
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

            if (isset($filters['followedByMe']) && $filters['followedByMe'] === true && auth()->check()) {
                $user = auth()->user();
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

            // Aplicar paginación
            $offset = ($page - 1) * $first;
            $companies = $query->skip($offset)->take($first)->get();

            // Calcular isFollowedByMe para contexto EXPLORE usando DataLoader (evita N+1)
            if ($context === 'EXPLORE' && auth()->check()) {
                $user = auth()->user();

                // Usar DataLoader para cargar todos los company IDs seguidos en 1 query
                $loader = app(\App\Features\CompanyManagement\GraphQL\DataLoaders\FollowedCompanyIdsByUserIdBatchLoader::class);
                $followedIds = $loader->load($user->id);

                $companies = $companies->map(function($company) use ($followedIds) {
                    $company->isFollowedByMe = in_array($company->id, $followedIds);
                    return $company;
                });
            }

            // Calcular información de paginación
            $hasNextPage = ($page * $first) < $total;

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
            throw new Error('Error al obtener empresas: ' . $e->getMessage());
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