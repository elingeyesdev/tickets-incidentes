<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Http\Requests\StoreTicketRequest;
use App\Features\TicketManagement\Http\Requests\UpdateTicketRequest;
use App\Features\TicketManagement\Http\Resources\TicketListResource;
use App\Features\TicketManagement\Http\Resources\TicketResource;
use App\Features\TicketManagement\Services\TicketService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * TicketController
 *
 * Controlador REST para gestión de tickets de soporte.
 * Arquitectura: Feature-First PURE
 * Feature: TicketManagement
 *
 * Métodos implementados:
 * - store() - POST /api/tickets (USER)
 * - index() - GET /api/tickets (AUTH)
 * - show() - GET /api/tickets/{ticket} (AUTH + Policy)
 * - update() - PATCH /api/tickets/{ticket} (AUTH + Policy)
 * - destroy() - DELETE /api/tickets/{ticket} (COMPANY_ADMIN + Policy)
 */
class TicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TicketService $ticketService
    ) {}

    #[OA\Post(
        path: '/api/tickets',
        operationId: 'create_ticket',
        description: 'Creates a new support ticket. Only users with USER role can create tickets. The ticket will be created with status "open" and a unique ticket_code (e.g., TKT-2025-00001) will be automatically generated. Requires JWT authentication with USER role.',
        summary: 'Create a new support ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'description', 'company_id', 'category_id'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        description: 'Ticket title (5-200 characters)',
                        type: 'string',
                        maxLength: 200,
                        minLength: 5,
                        example: 'Error al exportar reporte mensual'
                    ),
                    new OA\Property(
                        property: 'description',
                        description: 'Detailed description of the issue (10-2000 characters)',
                        type: 'string',
                        maxLength: 2000,
                        minLength: 10,
                        example: 'Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500. He intentado desde diferentes navegadores con el mismo resultado.'
                    ),
                    new OA\Property(
                        property: 'company_id',
                        description: 'UUID of the company this ticket belongs to',
                        type: 'string',
                        format: 'uuid',
                        example: '550e8400-e29b-41d4-a716-446655440000'
                    ),
                    new OA\Property(
                        property: 'category_id',
                        description: 'UUID of the ticket category (must be active and belong to the specified company)',
                        type: 'string',
                        format: 'uuid',
                        example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'
                    ),
                    new OA\Property(
                        property: 'priority',
                        description: 'Ticket priority level (optional, defaults to medium)',
                        type: 'string',
                        enum: ['low', 'medium', 'high'],
                        example: 'medium',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'area_id',
                        description: 'UUID of the area/department (optional, must be active and belong to the ticket company)',
                        type: 'string',
                        format: 'uuid',
                        example: '8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Tickets'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ticket created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Ticket creado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                new OA\Property(property: 'ticket_code', type: 'string', example: 'TKT-2025-00001'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid', example: '9b8c7d6e-5f4a-3b2c-1d0e-9f8e7d6c5b4a'),
                                new OA\Property(property: 'created_by_user_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                new OA\Property(property: 'owner_agent_id', type: 'string', format: 'uuid', example: null, nullable: true),
                                new OA\Property(property: 'title', type: 'string', example: 'Error al exportar reporte mensual'),
                                new OA\Property(property: 'description', type: 'string', example: 'Cuando intento exportar el reporte mensual de ventas...'),
                                new OA\Property(property: 'status', type: 'string', enum: ['open', 'pending', 'resolved', 'closed'], example: 'open'),
                                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium'),
                                new OA\Property(property: 'last_response_author_type', type: 'string', enum: ['none', 'user', 'agent'], example: 'none'),
                                new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(
                                    property: 'created_by_user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'owner_agent',
                                    type: 'object',
                                    example: null,
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'company',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'category',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Problemas Técnicos'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'area_id', type: 'string', format: 'uuid', nullable: true, example: null),
                                new OA\Property(
                                    property: 'area',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'responses_count', type: 'integer', example: 0),
                                new OA\Property(property: 'attachments_count', type: 'integer', example: 0),
                                new OA\Property(
                                    property: 'timeline',
                                    properties: [
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                        new OA\Property(property: 'first_response_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                        new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                        new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have USER role)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The title field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'title',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The title field is required.')
                                ),
                                new OA\Property(
                                    property: 'description',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The description must be at least 10 characters.')
                                ),
                                new OA\Property(
                                    property: 'company_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The selected company id is invalid.')
                                ),
                                new OA\Property(
                                    property: 'category_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'La categoría seleccionada no está activa.')
                                ),
                                new OA\Property(
                                    property: 'priority',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Priority must be one of: low, medium, high')
                                ),
                                new OA\Property(
                                    property: 'area_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The selected area is inactive.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * POST /api/tickets
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $ticket = $this->ticketService->create($request->validated(), $user);

        return response()->json([
            'message' => 'Ticket creado exitosamente',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    #[OA\Get(
        path: '/api/tickets',
        operationId: 'list_tickets',
        description: 'Returns paginated list of tickets with role-based filtering. USER sees only their own tickets. AGENT sees all tickets from their company. COMPANY_ADMIN sees all tickets from their company. Supports multiple filters, search, sorting, and pagination.',
        summary: 'List tickets with role-based visibility',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                description: 'Filter by ticket status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['open', 'pending', 'resolved', 'closed'])
            ),
            new OA\Parameter(
                name: 'category_id',
                description: 'Filter by category UUID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'priority',
                description: 'Filter by ticket priority',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high'])
            ),
            new OA\Parameter(
                name: 'area_id',
                description: 'Filter by area UUID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'owner_agent_id',
                description: 'Filter by assigned agent UUID. Use "null" for unassigned tickets, "me" for tickets assigned to authenticated user',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: '550e8400-e29b-41d4-a716-446655440000')
            ),
            new OA\Parameter(
                name: 'created_by_user_id',
                description: 'Filter by creator user UUID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'last_response_author_type',
                description: 'Filter by last response author type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['none', 'user', 'agent'])
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Search in ticket title and description (case-insensitive)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'created_from',
                description: 'Filter tickets created after this date (inclusive)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'created_to',
                description: 'Filter tickets created before this date (inclusive)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'created_after',
                description: 'Alternative to created_from (same behavior)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'created_before',
                description: 'Alternative to created_to (same behavior)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'Field to sort by',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'created_at', enum: ['created_at', 'updated_at', 'title', 'status'])
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'Sort direction',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Shorthand for sort_by (defaults to ascending). If provided without sort_order, will use "asc"',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'created_at')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Number of items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number for pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tickets list with pagination',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                    new OA\Property(property: 'ticket_code', type: 'string', example: 'TKT-2025-00001'),
                                    new OA\Property(property: 'title', type: 'string', example: 'Error al exportar reporte mensual'),
                                    new OA\Property(property: 'status', type: 'string', enum: ['open', 'pending', 'resolved', 'closed'], example: 'open'),
                                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'medium'),
                                    new OA\Property(property: 'last_response_author_type', type: 'string', enum: ['none', 'user', 'agent'], example: 'none'),
                                    new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'area_id', type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(property: 'created_by_user_id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'owner_agent_id', type: 'string', format: 'uuid', nullable: true),
                                    new OA\Property(property: 'creator_name', type: 'string', example: 'Juan Pérez'),
                                    new OA\Property(property: 'owner_agent_name', type: 'string', example: 'María García', nullable: true),
                                    new OA\Property(property: 'category_name', type: 'string', example: 'Problemas Técnicos'),
                                    new OA\Property(
                                        property: 'created_by_user',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'owner_agent',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'category',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'area',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'company',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'responses_count', type: 'integer', example: 3),
                                    new OA\Property(property: 'attachments_count', type: 'integer', example: 1),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 45),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * GET /api/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $filters = $request->only([
            'status',
            'category_id',
            'priority',
            'owner_agent_id',
            'created_by_user_id',
            'last_response_author_type',
            'search',
            'created_from',
            'created_to',
            'created_after',
            'created_before',
            'sort_by',
            'sort_order',
            'sort',
            'per_page',
        ]);

        // Parse 'sort' parameter if present (format: 'field' defaults to ASC)
        if (!empty($filters['sort']) && empty($filters['sort_by'])) {
            $filters['sort_by'] = $filters['sort'];
            $filters['sort_order'] = 'asc'; // Default to ascending when using 'sort' parameter
        }

        $tickets = $this->ticketService->list($filters, $user);

        return response()->json([
            'data' => TicketListResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/tickets/{ticket}',
        operationId: 'get_ticket',
        description: 'Returns detailed information about a specific ticket. Policy-based authorization: ticket creator can always view, agents and company admins can view tickets from their company. The ticket is identified by ticket_code (e.g., TKT-2025-00001) in the URL.',
        summary: 'Get a single ticket by ticket_code',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket details retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                new OA\Property(property: 'ticket_code', type: 'string', example: 'TKT-2025-00001'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'created_by_user_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'owner_agent_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'title', type: 'string', example: 'Error al exportar reporte mensual'),
                                new OA\Property(property: 'description', type: 'string', example: 'Cuando intento exportar el reporte mensual de ventas...'),
                                new OA\Property(property: 'status', type: 'string', enum: ['open', 'pending', 'resolved', 'closed'], example: 'pending'),
                                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high'], example: 'high'),
                                new OA\Property(property: 'last_response_author_type', type: 'string', enum: ['none', 'user', 'agent'], example: 'agent'),
                                new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                new OA\Property(
                                    property: 'created_by_user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan.perez@example.com'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'owner_agent',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'María García'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'company',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'category',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Problemas Técnicos'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'area_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(
                                    property: 'area',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string', example: 'Soporte Técnico'),
                                        new OA\Property(property: 'is_active', type: 'boolean'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'responses_count', type: 'integer', example: 3),
                                new OA\Property(property: 'attachments_count', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'timeline',
                                    properties: [
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                        new OA\Property(property: 'first_response_at', type: 'string', format: 'date-time', example: '2025-11-16T11:15:00+00:00', nullable: true),
                                        new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                        new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', example: null, nullable: true),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T12:45:00+00:00'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have permission to view this ticket)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * GET /api/tickets/{ticket}
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'creator.profile',
            'ownerAgent.profile',
            'company',
            'category',
        ]);
        $ticket->loadCount(['responses', 'attachments']);

        return response()->json([
            'data' => new TicketResource($ticket),
        ]);
    }

    #[OA\Patch(
        path: '/api/tickets/{ticket}',
        operationId: 'update_ticket',
        description: 'Updates ticket properties. Policy-based authorization: ticket creator can update only if status is "open", agents and company admins can update tickets from their company. Only title and category_id can be updated. Partial updates are supported (send only fields to update).',
        summary: 'Update a ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'title',
                        description: 'New ticket title (5-200 characters, optional)',
                        type: 'string',
                        maxLength: 200,
                        minLength: 5,
                        example: 'Error al exportar reportes - Actualizado'
                    ),
                    new OA\Property(
                        property: 'category_id',
                        description: 'New category UUID (must be active and belong to ticket company, optional)',
                        type: 'string',
                        format: 'uuid',
                        example: '8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d'
                    ),
                    new OA\Property(
                        property: 'priority',
                        description: 'New priority level (optional, must be low/medium/high)',
                        type: 'string',
                        enum: ['low', 'medium', 'high'],
                        example: 'high',
                        nullable: true
                    ),
                    new OA\Property(
                        property: 'area_id',
                        description: 'New area UUID (optional, must be active and belong to ticket company, can be null to remove area)',
                        type: 'string',
                        format: 'uuid',
                        example: '8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Ticket actualizado exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'ticket_code', type: 'string', example: 'TKT-2025-00001'),
                                new OA\Property(property: 'company_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'category_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'created_by_user_id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'owner_agent_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(property: 'title', type: 'string', example: 'Error al exportar reportes - Actualizado'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['open', 'pending', 'resolved', 'closed']),
                                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high']),
                                new OA\Property(property: 'last_response_author_type', type: 'string'),
                                new OA\Property(property: 'resolved_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'closed_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(
                                    property: 'created_by_user',
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'owner_agent',
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'company',
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'category',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'area_id', type: 'string', format: 'uuid', nullable: true),
                                new OA\Property(
                                    property: 'area',
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'responses_count', type: 'integer'),
                                new OA\Property(property: 'attachments_count', type: 'integer'),
                                new OA\Property(
                                    property: 'timeline',
                                    type: 'object'
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have permission to update this ticket or ticket status does not allow updates)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The title must be at least 5 characters.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'title',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The title must be at least 5 characters.')
                                ),
                                new OA\Property(
                                    property: 'category_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'La categoría seleccionada no está activa.')
                                ),
                                new OA\Property(
                                    property: 'priority',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'Priority must be one of: low, medium, high')
                                ),
                                new OA\Property(
                                    property: 'area_id',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'The selected area is inactive.')
                                ),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * PATCH /api/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket = $this->ticketService->update($ticket, $request->validated());

        return response()->json([
            'message' => 'Ticket actualizado exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    #[OA\Delete(
        path: '/api/tickets/{ticket}',
        operationId: 'delete_ticket',
        description: 'Permanently deletes a ticket. Only COMPANY_ADMIN role can delete tickets. Policy-based authorization: ticket must be in "closed" status to be deleted. This is a hard delete operation (not soft delete).',
        summary: 'Delete a ticket',
        security: [['bearerAuth' => []]],
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Ticket eliminado exitosamente'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated (missing or invalid JWT token)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (user does not have COMPANY_ADMIN role, does not own the company, or ticket is not closed)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Cannot delete ticket (ticket is not in closed status)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Only closed tickets can be deleted. Current status: pending'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * DELETE /api/tickets/{ticket}
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $this->ticketService->delete($ticket);

        return response()->json([
            'message' => 'Ticket eliminado exitosamente',
        ]);
    }
}
