<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Events\ResponseAdded;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Notifications\ResponseNotification;
use App\Features\TicketManagement\Requests\StoreResponseRequest;
use App\Features\TicketManagement\Requests\UpdateResponseRequest;
use App\Features\TicketManagement\Resources\TicketResponseResource;
use App\Features\TicketManagement\Services\ResponseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;

class TicketResponseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ResponseService $responseService
    ) {}

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