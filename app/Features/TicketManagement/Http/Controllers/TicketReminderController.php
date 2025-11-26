<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Mail\TicketReminderMail;
use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

/**
 * Ticket Reminder Controller
 *
 * Handles sending reminder emails to ticket creators.
 * Allows agents to manually notify users about pending tickets.
 *
 * Feature: Ticket Management
 * Base URL: /api/tickets/{ticket}
 */
class TicketReminderController extends Controller
{
    use AuthorizesRequests;

    #[OA\Post(
        path: '/api/tickets/{ticket}/remind',
        operationId: 'send_ticket_reminder',
        description: 'Sends a reminder email to the ticket creator. Only AGENT role users from the ticket\'s company can send reminders. The email notifies the user about the ticket status and encourages them to check for updates or respond.',
        summary: 'Send reminder email to ticket creator',
        security: [
            ['bearerAuth' => []],
        ],
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
                description: 'Reminder sent successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'message',
                            description: 'Success message',
                            type: 'string',
                            example: 'Recordatorio enviado exitosamente'
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
     * Envía recordatorio por email al creador del ticket
     *
     * @param Ticket $ticket
     * @return JsonResponse
     */
    public function sendReminder(Ticket $ticket): JsonResponse
    {
        $this->authorize('sendReminder', $ticket);

        // Cargar relación creator para el email
        $ticket->load('creator.profile');

        // Enviar email de recordatorio
        Mail::to($ticket->creator->email)
            ->send(new TicketReminderMail($ticket));

        return response()->json([
            'message' => 'Recordatorio enviado exitosamente',
        ], 200);
    }
}
