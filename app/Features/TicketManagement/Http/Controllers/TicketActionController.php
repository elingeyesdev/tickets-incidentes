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

class TicketActionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

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
