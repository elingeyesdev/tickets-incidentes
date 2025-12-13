<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Resources\CompanyInvitationResource;
use App\Features\CompanyManagement\Services\CompanyInvitationService;
use App\Features\UserManagement\Http\Resources\UserMinimalResource;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * CompanyInvitationController
 *
 * Handles API endpoints for Company Admin to manage agent invitations.
 */
class CompanyInvitationController extends Controller
{
    public function __construct(
        private CompanyInvitationService $invitationService
    ) {
    }

    /**
     * GET /api/company/invitations
     * List all invitations for the company
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $status = $request->query('status');

        $invitations = $this->invitationService->getInvitationsByCompany($companyId, $status);

        return response()->json([
            'success' => true,
            'data' => CompanyInvitationResource::collection($invitations),
        ]);
    }

    /**
     * POST /api/company/invitations
     * Create a new invitation
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'message' => 'nullable|string|max:1000',
        ]);

        $companyId = JWTHelper::getActiveCompanyId();
        $invitedBy = JWTHelper::getAuthenticatedUser()['id'];

        $invitation = $this->invitationService->createInvitation(
            $companyId,
            $request->input('user_id'),
            $invitedBy,
            $request->input('message')
        );

        return response()->json([
            'success' => true,
            'message' => 'Invitación enviada exitosamente',
            'data' => new CompanyInvitationResource($invitation),
        ], 201);
    }

    /**
     * DELETE /api/company/invitations/{id}
     * Cancel a pending invitation
     */
    public function destroy(string $id): JsonResponse
    {
        $companyId = JWTHelper::getActiveCompanyId();

        $invitation = $this->invitationService->cancelInvitation($id, $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Invitación cancelada',
            'data' => new CompanyInvitationResource($invitation),
        ]);
    }

    /**
     * GET /api/company/agents
     * List all agents for the company with complete agent information
     * 
     * Includes: profile, ticket statistics, last activity
     * This endpoint is specific for agent management, not for general user listing.
     */
    public function agents(Request $request): JsonResponse
    {
        $companyId = JWTHelper::getActiveCompanyId();

        $agents = $this->invitationService->getAgentsForCompany($companyId);

        // Transform UserRole collection to include comprehensive agent info
        $agentsData = $agents->map(function ($userRole) use ($companyId) {
            $user = $userRole->user;
            $profile = $user->profile;

            // Count tickets assigned to this agent in this company
            $ticketsAssigned = \App\Features\TicketManagement\Models\Ticket::where('owner_agent_id', $user->id)
                ->where('company_id', $companyId)
                ->count();

            // Count resolved tickets (includes both 'resolved' and 'closed' - matching dashboard)
            $ticketsResolved = \App\Features\TicketManagement\Models\Ticket::where('owner_agent_id', $user->id)
                ->where('company_id', $companyId)
                ->whereIn('status', ['resolved', 'closed'])
                ->count();

            return [
                'id' => $userRole->id, // UserRole ID (for removal)
                'user_id' => $user->id,
                'email' => $user->email,
                'display_name' => $profile
                    ? trim("{$profile->first_name} {$profile->last_name}") ?: $user->email
                    : $user->email,
                'first_name' => $profile?->first_name,
                'last_name' => $profile?->last_name,
                'avatar_url' => $profile?->avatar_url,
                'phone_number' => $profile?->phone_number,
                'assigned_at' => $userRole->assigned_at?->toIso8601String(),
                'is_active' => $userRole->is_active,
                'last_activity_at' => $user->last_activity_at?->toIso8601String(),
                // Ticket statistics
                'tickets_assigned' => $ticketsAssigned,
                'tickets_resolved' => $ticketsResolved,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $agentsData,
            'meta' => [
                'total' => $agents->count(),
            ],
        ]);
    }

    /**
     * DELETE /api/company/agents/{userRoleId}
     * Remove an agent from the company
     */
    public function removeAgent(string $userRoleId): JsonResponse
    {
        $companyId = JWTHelper::getActiveCompanyId();

        $this->invitationService->removeAgent($userRoleId, $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Agente removido exitosamente',
        ]);
    }

    /**
     * GET /api/company/invitations/search-users
     * Search users that can be invited
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2|max:100',
        ]);

        $companyId = JWTHelper::getActiveCompanyId();
        $search = $request->query('search');

        $users = $this->invitationService->searchUsersForInvitation($companyId, $search);

        // Transform users
        $usersData = $users->map(function ($user) {
            $profile = $user->profile;
            return [
                'id' => $user->id,
                'email' => $user->email,
                'display_name' => $profile
                    ? trim("{$profile->first_name} {$profile->last_name}") ?: $user->email
                    : $user->email,
                'avatar_url' => $profile?->avatar_url,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $usersData,
        ]);
    }
}
