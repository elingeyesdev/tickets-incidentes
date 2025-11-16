<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Requests\AssignTicketRequest;
use App\Features\TicketManagement\Requests\CloseTicketRequest;
use App\Features\TicketManagement\Requests\ReopenTicketRequest;
use App\Features\TicketManagement\Requests\ResolveTicketRequest;
use App\Features\TicketManagement\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TicketActionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TicketService $ticketService
    ) {}

    /**
     * POST /api/tickets/{ticket}/resolve
     */
    public function resolve(ResolveTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // TODO: Implementar en siguiente agente
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * POST /api/tickets/{ticket}/close
     */
    public function close(CloseTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // TODO: Implementar en siguiente agente
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * POST /api/tickets/{ticket}/reopen
     */
    public function reopen(ReopenTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // TODO: Implementar en siguiente agente
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * POST /api/tickets/{ticket}/assign
     */
    public function assign(AssignTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // TODO: Implementar en siguiente agente
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
