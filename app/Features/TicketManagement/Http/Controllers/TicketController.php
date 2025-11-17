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

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TicketService $ticketService
    ) {}

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

    /**
     * GET /api/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $filters = $request->only([
            'status',
            'category_id',
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
