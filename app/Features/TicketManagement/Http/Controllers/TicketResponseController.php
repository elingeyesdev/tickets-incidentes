<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Events\ResponseAdded;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Notifications\ResponseNotification;
use App\Features\TicketManagement\Http\Requests\StoreResponseRequest;
use App\Features\TicketManagement\Http\Requests\UpdateResponseRequest;
use App\Features\TicketManagement\Http\Resources\TicketResponseResource;
use App\Features\TicketManagement\Services\ResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use OpenApi\Attributes as OA;

/**
 * TicketResponseController
 *
 * Controlador REST para gestión de respuestas en tickets de soporte.
 * Arquitectura: Feature-First PURE
 * Feature: TicketManagement
 *
 * Métodos implementados:
 * - store() - POST /api/tickets/{ticket}/responses (AUTH + Policy)
 * - index() - GET /api/tickets/{ticket}/responses (AUTH + Policy)
 * - show() - GET /api/tickets/{ticket}/responses/{response} (AUTH + Policy)
 * - update() - PATCH /api/tickets/{ticket}/responses/{response} (AUTH + Policy)
 * - destroy() - DELETE /api/tickets/{ticket}/responses/{response} (AUTH + Policy)
 *
 * Características:
 * - Autor type automático (user/agent) basado en rol JWT
 * - Trigger de auto-assignment cuando agente responde primero
 * - Edición/eliminación limitada a 30 minutos
 * - No se puede interactuar con tickets cerrados
 */
class TicketResponseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ResponseService $responseService
    ) {}

    #[OA\Post(
        path: '/api/tickets/{ticket}/responses',
        operationId: 'create_response',
        description: 'Creates a new response in a ticket. The author_type (user/agent) is automatically determined based on the authenticated user\'s JWT role. If an AGENT responds first, the ticket is auto-assigned to them via database trigger. Cannot respond to CLOSED tickets. Triggers ResponseAdded event. USER role: responds as "user" type. AGENT role: responds as "agent" type.',
        summary: 'Create a new response in a ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(
                        property: 'content',
                        description: 'Response content text (1-5000 characters). Can include plain text or formatted content.',
                        type: 'string',
                        maxLength: 5000,
                        minLength: 1,
                        example: 'Gracias por contactarnos. He revisado su caso y el problema se debe a una configuración incorrecta en el módulo de exportación. Procederé a corregirlo inmediatamente.'
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Responses'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Response created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Respuesta creada exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d'),
                                new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                new OA\Property(property: 'content', type: 'string', example: 'Gracias por contactarnos. He revisado su caso...'),
                                new OA\Property(
                                    property: 'author_type',
                                    description: 'Automatically set based on JWT role: "user" for USER role, "agent" for AGENT role',
                                    type: 'string',
                                    enum: ['user', 'agent'],
                                    example: 'agent'
                                ),
                                new OA\Property(
                                    property: 'author',
                                    description: 'Author information (loaded relationship)',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                        new OA\Property(property: 'name', type: 'string', example: 'María García'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria.garcia@company.com'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    description: 'Empty array for new response (attachments uploaded separately)',
                                    type: 'array',
                                    items: new OA\Items(type: 'object'),
                                    example: []
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T14:30:00+00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T14:30:00+00:00'),
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
                description: 'Forbidden - user lacks permission to respond to this ticket OR ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'No se puede responder a un ticket cerrado'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The content field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'content',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The content field is required.', 'The content must not be greater than 5000 characters.']
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
     * POST /api/tickets/{ticket}/responses
     */
    public function store(StoreResponseRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', [TicketResponse::class, $ticket]);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede responder a un ticket cerrado',
            ], 403);
        }

        $user = JWTHelper::getAuthenticatedUser();
        $response = $this->responseService->create($ticket, $request->validated(), $user);

        // Refrescar el ticket para obtener cambios del trigger
        $ticket->refresh();

        // Disparar evento ResponseAdded
        event(new ResponseAdded($response));

        // TODO: Mover notificaciones a un Listener del evento ResponseAdded
        // El User model necesita el trait Notifiable primero
        // Enviar notificación a la parte relevante
        // Si USER responde → notificar al AGENT asignado
        // Si AGENT responde → notificar al USER creador del ticket
        // if ($response->author_type->value === 'user' && $ticket->ownerAgent) {
        //     $ticket->ownerAgent->notify(new ResponseNotification($response));
        // } elseif ($response->author_type->value === 'agent' && $ticket->creator) {
        //     $ticket->creator->notify(new ResponseNotification($response));
        // }

        return response()->json([
            'message' => 'Respuesta creada exitosamente',
            'data' => new TicketResponseResource($response),
        ], 201);
    }

    #[OA\Get(
        path: '/api/tickets/{ticket}/responses',
        operationId: 'list_responses',
        description: 'Returns all responses for a specific ticket ordered by created_at ASC (chronological conversation order). Ticket creator can view their ticket responses. AGENT role users can view responses for tickets in their company. Each response includes author information and any attachments.',
        summary: 'List all responses for a ticket',
        security: [['bearerAuth' => []]],
        tags: ['Ticket Responses'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of responses retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d'),
                                    new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                    new OA\Property(property: 'author_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                    new OA\Property(property: 'content', type: 'string', example: 'Gracias por la respuesta. ¿Cuándo estará solucionado?'),
                                    new OA\Property(
                                        property: 'author_type',
                                        type: 'string',
                                        enum: ['user', 'agent'],
                                        example: 'user'
                                    ),
                                    new OA\Property(
                                        property: 'author',
                                        description: 'Author information (if relationship loaded)',
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'attachments',
                                        description: 'Response attachments (if relationship loaded)',
                                        type: 'array',
                                        items: new OA\Items(type: 'object'),
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T10:30:00+00:00'),
                                ],
                                type: 'object'
                            )
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
                description: 'Forbidden - user lacks permission to view this ticket\'s responses',
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
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * GET /api/tickets/{ticket}/responses
     */
    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('viewAny', [TicketResponse::class, $ticket]);

        $responses = $this->responseService->list($ticket);

        return response()->json([
            'data' => TicketResponseResource::collection($responses),
        ]);
    }

    #[OA\Get(
        path: '/api/tickets/{ticket}/responses/{response}',
        operationId: 'get_response',
        description: 'Returns detailed information about a specific response including author details and attachments. Validates that the response belongs to the specified ticket. Ticket creator can view responses from their ticket. AGENT role users can view responses for tickets in their company.',
        summary: 'Get a single response by ID',
        security: [['bearerAuth' => []]],
        tags: ['Ticket Responses'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
            new OA\Parameter(
                name: 'response',
                description: 'Response UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Response details retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d'),
                                new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                new OA\Property(property: 'content', type: 'string', example: 'He identificado el problema. Procederé a solucionarlo.'),
                                new OA\Property(
                                    property: 'author_type',
                                    type: 'string',
                                    enum: ['user', 'agent'],
                                    example: 'agent'
                                ),
                                new OA\Property(
                                    property: 'author',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                        new OA\Property(property: 'name', type: 'string', example: 'María García'),
                                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria.garcia@company.com'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'response_id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'uploaded_by_user_id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'uploaded_by_name', type: 'string', example: 'María García'),
                                            new OA\Property(property: 'file_name', type: 'string', example: 'screenshot.png'),
                                            new OA\Property(property: 'file_url', type: 'string', example: '/storage/tickets/attachments/abc123.png'),
                                            new OA\Property(property: 'file_type', type: 'string', example: 'image/png'),
                                            new OA\Property(property: 'file_size_bytes', type: 'integer', example: 245760),
                                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T11:15:00+00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T11:15:00+00:00'),
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
                description: 'Forbidden - user lacks permission to view this ticket\'s responses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket not found OR response does not belong to ticket',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'RESPONSE_NOT_FOUND'),
                                new OA\Property(property: 'message', type: 'string', example: 'La respuesta no pertenece a este ticket'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    /**
     * GET /api/tickets/{ticket}/responses/{response}
     */
    public function show(Ticket $ticket, TicketResponse $response): JsonResponse
    {
        $this->authorize('viewAny', [TicketResponse::class, $ticket]);

        // Verificar que la response pertenece al ticket
        if ($response->ticket_id !== $ticket->id) {
            return response()->json([
                'code' => 'RESPONSE_NOT_FOUND',
                'message' => 'La respuesta no pertenece a este ticket',
            ], 404);
        }

        // Cargar relaciones necesarias
        $response->load(['author', 'attachments']);

        return response()->json([
            'data' => new TicketResponseResource($response),
        ]);
    }

    #[OA\Patch(
        path: '/api/tickets/{ticket}/responses/{response}',
        operationId: 'update_response',
        description: 'Updates the content of a response. Only the original author can update their response and only within 30 minutes of creation. Cannot update responses in CLOSED tickets. The author_type cannot be changed.',
        summary: 'Update a response',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(
                        property: 'content',
                        description: 'Updated response content (1-5000 characters)',
                        type: 'string',
                        maxLength: 5000,
                        minLength: 1,
                        example: 'He identificado el problema y ya está corregido. Por favor intenta nuevamente. Si el error persiste, adjunta una captura de pantalla.'
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Responses'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
            new OA\Parameter(
                name: 'response',
                description: 'Response UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Response updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Respuesta actualizada exitosamente'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d'),
                                new OA\Property(property: 'ticket_id', type: 'string', format: 'uuid', example: '7c9e6679-7425-40de-944b-e07fc1f90ae7'),
                                new OA\Property(property: 'author_id', type: 'string', format: 'uuid', example: 'a1b2c3d4-e5f6-7890-abcd-ef1234567890'),
                                new OA\Property(property: 'content', type: 'string', example: 'He identificado el problema y ya está corregido...'),
                                new OA\Property(
                                    property: 'author_type',
                                    type: 'string',
                                    enum: ['user', 'agent'],
                                    example: 'agent'
                                ),
                                new OA\Property(
                                    property: 'author',
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'attachments',
                                    type: 'array',
                                    items: new OA\Items(type: 'object'),
                                    nullable: true
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-11-16T11:15:00+00:00'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-11-16T11:30:00+00:00'),
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
                description: 'Forbidden - user is not the author, 30 minute window expired, or ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'No se puede actualizar respuesta de un ticket cerrado'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket or response not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The content field is required.'),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'content',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The content field is required.', 'The content must not be greater than 5000 characters.']
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
     * PATCH /api/tickets/{ticket}/responses/{response}
     */
    public function update(
        UpdateResponseRequest $request,
        Ticket $ticket,
        TicketResponse $response
    ): JsonResponse {
        $this->authorize('update', $response);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede actualizar respuesta de un ticket cerrado',
            ], 403);
        }

        $response = $this->responseService->update($response, $request->validated());

        return response()->json([
            'message' => 'Respuesta actualizada exitosamente',
            'data' => new TicketResponseResource($response),
        ]);
    }

    #[OA\Delete(
        path: '/api/tickets/{ticket}/responses/{response}',
        operationId: 'delete_response',
        description: 'Permanently deletes a response from a ticket. Only the original author can delete their response and only within 30 minutes of creation. Cannot delete responses in CLOSED tickets. This is a hard delete operation.',
        summary: 'Delete a response',
        security: [['bearerAuth' => []]],
        tags: ['Ticket Responses'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket code (e.g., TKT-2025-00001)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^TKT-\d{4}-\d{5}$', example: 'TKT-2025-00001')
            ),
            new OA\Parameter(
                name: 'response',
                description: 'Response UUID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '9d8c7b6a-5e4f-3a2b-1c0d-9e8f7a6b5c4d')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Response deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Respuesta eliminada exitosamente'),
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
                description: 'Forbidden - user is not the author, 30 minute window expired, or ticket is closed',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'TICKET_CLOSED'),
                                new OA\Property(property: 'message', type: 'string', example: 'No se puede eliminar respuesta de un ticket cerrado'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket or response not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No query results for model [Ticket].'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * DELETE /api/tickets/{ticket}/responses/{response}
     */
    public function destroy(Ticket $ticket, TicketResponse $response): JsonResponse
    {
        $this->authorize('delete', $response);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede eliminar respuesta de un ticket cerrado',
            ], 403);
        }

        $this->responseService->delete($response);

        return response()->json([
            'message' => 'Respuesta eliminada exitosamente',
        ]);
    }
}