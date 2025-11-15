<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
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