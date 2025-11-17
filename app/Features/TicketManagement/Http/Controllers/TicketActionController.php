<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Http\Requests\TicketActionRequest;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Http\Resources\TicketResource;
use App\Features\TicketManagement\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OpenApi\Attributes as OA;

/**
 * Ticket Action Controller
 *
 * Handles ticket state transitions and actions:
 * - Resolve (mark ticket as resolved)
 * - Close (mark ticket as closed)
 * - Reopen (reopen resolved/closed ticket)
 * - Assign (assign ticket to agent)
 *
 * All actions are policy-protected and validated through TicketActionRequest.
 * Business logic is delegated to TicketService.
 *
 * Feature: Ticket Management
 * Base URL: /api/tickets/{ticket}
 */
class TicketActionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    #[OA\Post(
        path: '/api/tickets/{ticket}/resolve',
        operationId: 'resolve_ticket',
        description: 'Marks a ticket as resolved by changing its status from OPEN or PENDING to RESOLVED. Sets the resolved_at timestamp. Only AGENT role users from the ticket\'s company can resolve tickets. Cannot resolve tickets that are already RESOLVED or CLOSED. Triggers TicketResolved event.',
        summary: 'Resolve a ticket',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Optional resolution note',
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'resolution_note',
                        description: 'Optional note explaining the resolution (max 5000 chars)',
                        type: 'string',
                        maxLength: 5000,
                        example: 'Issue was resolved by updating the database configuration.',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Actions'],
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
                description: 'Ticket resolved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Ticket marcado como resuelto exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Updated ticket resource with status=resolved and resolved_at timestamp',
                            type: 'object',
                            example: [
                                'id' => '550e8400-e29b-41d4-a716-446655440000',
                                'ticket_code' => 'TKT-2025-00001',
                                'status' => 'resolved',
                                'resolved_at' => '2025-11-16T14:30:00+00:00',
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state transition',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Error message',
                            type: 'string',
                            enum: ['El ticket ya está resuelto', 'El ticket ya está cerrado']
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
                description: 'Forbidden - user lacks AGENT role or does not belong to ticket company',
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
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The resolution note must not be greater than 5000 characters.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['resolution_note' => ['The resolution note must not be greater than 5000 characters.']]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Resuelve un ticket (cambia status a resolved)
     *
     * @param Ticket $ticket
     * @param TicketActionRequest $request
     * @return JsonResponse
     */
    public function resolve(Ticket $ticket, TicketActionRequest $request): JsonResponse
    {
        $this->authorize('resolve', $ticket);

        try {
            $validated = $request->validated();
            $updatedTicket = $this->ticketService->resolve($ticket, $validated);

            return response()->json([
                'message' => 'Ticket marcado como resuelto exitosamente',
                'data' => new TicketResource($updatedTicket),
            ], 200);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'ALREADY_RESOLVED') {
                return response()->json([
                    'message' => 'El ticket ya está resuelto',
                ], 400);
            }

            if ($e->getMessage() === 'ALREADY_CLOSED') {
                return response()->json([
                    'message' => 'El ticket ya está cerrado',
                ], 400);
            }

            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/tickets/{ticket}/close',
        operationId: 'close_ticket',
        description: 'Closes a ticket by changing its status to CLOSED and setting closed_at timestamp. AGENT role users can close any ticket from their company regardless of current status. USER role (ticket creator) can only close tickets in RESOLVED status. Cannot close tickets that are already CLOSED. Triggers TicketClosed event.',
        summary: 'Close a ticket',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Optional close note',
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'close_note',
                        description: 'Optional note explaining why the ticket is being closed (max 5000 chars)',
                        type: 'string',
                        maxLength: 5000,
                        example: 'Closing ticket as the issue has been resolved and confirmed by the user.',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Actions'],
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
                description: 'Ticket closed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Ticket cerrado exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Updated ticket resource with status=closed and closed_at timestamp',
                            type: 'object',
                            example: [
                                'id' => '550e8400-e29b-41d4-a716-446655440000',
                                'ticket_code' => 'TKT-2025-00001',
                                'status' => 'closed',
                                'closed_at' => '2025-11-16T14:30:00+00:00',
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - ticket already closed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Error message',
                            type: 'string',
                            example: 'El ticket ya está cerrado'
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
                description: 'Forbidden - USER can only close RESOLVED tickets, AGENT must belong to ticket company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'This action is unauthorized.'
                        ),
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
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The close note must not be greater than 5000 characters.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['close_note' => ['The close note must not be greater than 5000 characters.']]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Cierra un ticket (cambia status a closed)
     *
     * @param Ticket $ticket
     * @param TicketActionRequest $request
     * @return JsonResponse
     */
    public function close(Ticket $ticket, TicketActionRequest $request): JsonResponse
    {
        $this->authorize('close', $ticket);

        try {
            $validated = $request->validated();
            $updatedTicket = $this->ticketService->close($ticket, $validated);

            return response()->json([
                'message' => 'Ticket cerrado exitosamente',
                'data' => new TicketResource($updatedTicket),
            ], 200);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'ALREADY_CLOSED') {
                return response()->json([
                    'message' => 'El ticket ya está cerrado',
                ], 400);
            }

            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/tickets/{ticket}/reopen',
        operationId: 'reopen_ticket',
        description: 'Reopens a ticket by changing its status from RESOLVED or CLOSED back to PENDING. Clears resolved_at and closed_at timestamps. Ticket creator (USER) can reopen within 30 days of closure. AGENT can reopen without time restrictions. Cannot reopen tickets in OPEN or PENDING status. Triggers TicketReopened event.',
        summary: 'Reopen a ticket',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Optional reopen reason',
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'reopen_reason',
                        description: 'Optional note explaining why the ticket is being reopened (max 5000 chars)',
                        type: 'string',
                        maxLength: 5000,
                        example: 'The issue has reoccurred and needs further investigation.',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Actions'],
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
                description: 'Ticket reopened successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Ticket reabierto exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Updated ticket resource with status=pending, resolved_at and closed_at set to null',
                            type: 'object',
                            example: [
                                'id' => '550e8400-e29b-41d4-a716-446655440000',
                                'ticket_code' => 'TKT-2025-00001',
                                'status' => 'pending',
                                'resolved_at' => null,
                                'closed_at' => null,
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid state for reopening',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Error message',
                            type: 'string',
                            example: 'El ticket no puede ser reabierto'
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
                description: 'Forbidden - USER can only reopen within 30 days of closure, AGENT must belong to ticket company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'This action is unauthorized.'
                        ),
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
            new OA\Response(
                response: 422,
                description: 'Validation error - reopen restrictions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'The reopen reason must not be greater than 5000 characters.'
                        ),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'can_reopen' => ['No puedes reabrir un ticket cerrado después de 30 días.'],
                                'reopen_reason' => ['The reopen reason must not be greater than 5000 characters.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Reabre un ticket (cambia status a pending)
     *
     * @param Ticket $ticket
     * @param TicketActionRequest $request
     * @return JsonResponse
     */
    public function reopen(Ticket $ticket, TicketActionRequest $request): JsonResponse
    {
        $this->authorize('reopen', $ticket);

        try {
            $validated = $request->validated();
            $updatedTicket = $this->ticketService->reopen($ticket, $validated);

            return response()->json([
                'message' => 'Ticket reabierto exitosamente',
                'data' => new TicketResource($updatedTicket),
            ], 200);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'CANNOT_REOPEN') {
                return response()->json([
                    'message' => 'El ticket no puede ser reabierto',
                ], 400);
            }

            throw $e;
        }
    }

    #[OA\Post(
        path: '/api/tickets/{ticket}/assign',
        operationId: 'assign_ticket',
        description: 'Assigns a ticket to a specific agent by updating the owner_agent_id field. Only AGENT role users from the ticket\'s company can assign tickets. The target agent must have AGENT role and belong to the same company as the ticket. Triggers TicketAssigned event and sends notification to the assigned agent.',
        summary: 'Assign ticket to agent',
        security: [
            ['bearerAuth' => []],
        ],
        requestBody: new OA\RequestBody(
            description: 'Agent assignment data',
            required: true,
            content: new OA\JsonContent(
                required: ['new_agent_id'],
                properties: [
                    new OA\Property(
                        property: 'new_agent_id',
                        description: 'UUID of the agent to assign the ticket to (must have AGENT role in ticket company)',
                        type: 'string',
                        format: 'uuid',
                        example: '660e8400-e29b-41d4-a716-446655440000'
                    ),
                    new OA\Property(
                        property: 'assignment_note',
                        description: 'Optional note explaining the assignment (max 5000 chars)',
                        type: 'string',
                        maxLength: 5000,
                        example: 'Assigning to John as he has expertise in database issues.',
                        nullable: true
                    ),
                ],
                type: 'object'
            )
        ),
        tags: ['Ticket Actions'],
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
                description: 'Ticket assigned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Ticket asignado exitosamente'
                        ),
                        new OA\Property(
                            property: 'data',
                            description: 'Updated ticket resource with new owner_agent_id',
                            type: 'object',
                            example: [
                                'id' => '550e8400-e29b-41d4-a716-446655440000',
                                'ticket_code' => 'TKT-2025-00001',
                                'owner_agent_id' => '660e8400-e29b-41d4-a716-446655440000',
                                'owner_agent' => [
                                    'id' => '660e8400-e29b-41d4-a716-446655440000',
                                    'name' => 'John Doe',
                                ],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid agent role or company mismatch',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Error message',
                            type: 'string',
                            example: 'El usuario no tiene rol de agente o pertenece a otra empresa'
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
                description: 'Forbidden - user lacks AGENT role or does not belong to ticket company',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'This action is unauthorized.'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Ticket or agent not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'No query results for model [Ticket].'
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'The new agent id field is required.'
                        ),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'new_agent_id' => [
                                    'The new agent id field is required.',
                                    'The new agent id must be a valid UUID.',
                                    'El usuario no tiene rol de agente o pertenece a otra empresa.',
                                ],
                                'assignment_note' => ['The assignment note must not be greater than 5000 characters.'],
                            ]
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    /**
     * Asigna un ticket a un agente
     *
     * @param Ticket $ticket
     * @param TicketActionRequest $request
     * @return JsonResponse
     */
    public function assign(Ticket $ticket, TicketActionRequest $request): JsonResponse
    {
        $this->authorize('assign', $ticket);

        try {
            $validated = $request->validated();
            $updatedTicket = $this->ticketService->assign($ticket, $validated);

            return response()->json([
                'message' => 'Ticket asignado exitosamente',
                'data' => new TicketResource($updatedTicket),
            ], 200);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'INVALID_AGENT_ROLE') {
                return response()->json([
                    'message' => 'El usuario no tiene rol de agente o pertenece a otra empresa',
                ], 400);
            }

            throw $e;
        }
    }
}
