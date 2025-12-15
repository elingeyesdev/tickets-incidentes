<?php

declare(strict_types=1);

namespace App\Features\AuditLog\Http\Controllers;

use App\Features\AuditLog\Http\Resources\ActivityLogResource;
use App\Features\AuditLog\Services\ActivityLogService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * ActivityLogController
 *
 * Controlador para consultar registros de actividad.
 */
class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    #[OA\Get(
        path: '/api/activity-logs',
        operationId: 'list_activity_logs',
        description: 'Returns paginated activity logs for the authenticated user or all users (admin only)',
        summary: 'List activity logs',
        security: [['bearerAuth' => []]],
        tags: ['Activity Logs'],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                description: 'Filter by user ID (admin only)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'action',
                description: 'Filter by specific action',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category',
                description: 'Filter by category',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['authentication', 'tickets', 'users', 'companies'])
            ),
            new OA\Parameter(
                name: 'entity_type',
                description: 'Filter by entity type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'entity_id',
                description: 'Filter by entity ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'from',
                description: 'Filter from date (ISO 8601 format)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'to',
                description: 'Filter to date (ISO 8601 format)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Activity logs retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();
        // MIGRADO: Usar rol ACTIVO del usuario
        $isAdmin = JWTHelper::isActiveRoleOneOf(['PLATFORM_ADMIN', 'COMPANY_ADMIN']);

        // Determinar qué usuario consultar
        $targetUserId = $request->query('user_id');

        // Solo admins pueden ver logs de otros usuarios
        if ($targetUserId && $targetUserId !== $user->id && !$isAdmin) {
            return response()->json([
                'message' => 'No tienes permiso para ver la actividad de otros usuarios',
            ], 403);
        }

        // Si no es admin y no especificó user_id, mostrar solo sus logs
        if (!$isAdmin && !$targetUserId) {
            $targetUserId = $user->id;
        }

        // Construir query
        $query = \App\Features\AuditLog\Models\ActivityLog::query()
            ->orderBy('created_at', 'desc');

        // Filtrar por usuario si se especificó
        if ($targetUserId) {
            $query->forUser($targetUserId);
        }

        // Filtrar por acción
        if ($action = $request->query('action')) {
            $query->forAction($action);
        }

        // Filtrar por categoría
        if ($category = $request->query('category')) {
            match ($category) {
                'authentication' => $query->authActions(),
                'tickets' => $query->ticketActions(),
                'users' => $query->userActions(),
                'companies' => $query->companyActions(),
                default => null,
            };
        }

        // Filtrar por entidad
        if ($entityType = $request->query('entity_type')) {
            $entityId = $request->query('entity_id');
            $query->forEntity($entityType, $entityId);
        }

        // Filtrar por rango de fechas
        if ($from = $request->query('from')) {
            try {
                $fromDate = new \DateTime($from);
                $fromDate->setTime(0, 0, 0); // Inicio del día
                $query->where('created_at', '>=', $fromDate);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }
        if ($to = $request->query('to')) {
            try {
                $toDate = new \DateTime($to);
                $toDate->setTime(23, 59, 59); // Fin del día
                $query->where('created_at', '<=', $toDate);
            } catch (\Exception $e) {
                // Ignorar fecha inválida
            }
        }

        // Paginación
        $perPage = min($request->query('per_page', 15), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/activity-logs/my',
        operationId: 'my_activity_logs',
        description: 'Returns paginated activity logs for the authenticated user',
        summary: 'Get my activity logs',
        security: [['bearerAuth' => []]],
        tags: ['Activity Logs'],
        parameters: [
            new OA\Parameter(
                name: 'category',
                description: 'Filter by category',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['authentication', 'tickets', 'users', 'companies'])
            ),
            new OA\Parameter(
                name: 'from',
                description: 'Filter from date (ISO 8601 format)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'to',
                description: 'Filter to date (ISO 8601 format)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity logs retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function myActivity(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $perPage = min($request->query('per_page', 15), 100);
        $category = $request->query('category');
        $from = $request->query('from');
        $to = $request->query('to');

        // Build query manually for date filters
        $query = \App\Features\AuditLog\Models\ActivityLog::query()
            ->forUser($user->id)
            ->orderBy('created_at', 'desc');

        // Apply category filter
        if ($category) {
            match ($category) {
                'authentication' => $query->authActions(),
                'tickets' => $query->ticketActions(),
                'users' => $query->userActions(),
                'companies' => $query->companyActions(),
                default => null,
            };
        }

        // Apply date filters
        if ($from) {
            try {
                $fromDate = new \DateTime($from);
                $fromDate->setTime(0, 0, 0); // Start of day
                $query->where('created_at', '>=', $fromDate);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }
        if ($to) {
            try {
                $toDate = new \DateTime($to);
                $toDate->setTime(23, 59, 59); // End of day
                $query->where('created_at', '<=', $toDate);
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/activity-logs/entity/{entityType}/{entityId}',
        operationId: 'entity_activity_logs',
        description: 'Returns paginated activity logs for a specific entity (ticket, user, etc)',
        summary: 'Get entity activity logs',
        security: [['bearerAuth' => []]],
        tags: ['Activity Logs'],
        parameters: [
            new OA\Parameter(
                name: 'entityType',
                description: 'Entity type (ticket, user, company)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'entityId',
                description: 'Entity ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activity logs retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function entityActivity(Request $request, string $entityType, string $entityId): JsonResponse
    {
        // Verificar permisos según el tipo de entidad
        $user = JWTHelper::getAuthenticatedUser();
        // MIGRADO: Usar rol ACTIVO del usuario
        $isAdmin = JWTHelper::isActiveRoleOneOf(['PLATFORM_ADMIN', 'COMPANY_ADMIN']);

        // Para tickets, verificar que el usuario tenga acceso al ticket
        if ($entityType === 'ticket' && !$isAdmin) {
            $ticket = \App\Features\TicketManagement\Models\Ticket::find($entityId);
            if (!$ticket || ($ticket->created_by_user_id !== $user->id && $ticket->owner_agent_id !== $user->id)) {
                return response()->json([
                    'message' => 'No tienes permiso para ver la actividad de este ticket',
                ], 403);
            }
        }

        // Para usuarios, solo pueden ver su propia actividad (a menos que sean admin)
        if ($entityType === 'user' && $entityId !== $user->id && !$isAdmin) {
            return response()->json([
                'message' => 'No tienes permiso para ver la actividad de este usuario',
            ], 403);
        }

        $perPage = min($request->query('per_page', 15), 100);
        $logs = $this->activityLogService->getEntityActivity($entityType, $entityId, $perPage);

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
