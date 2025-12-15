<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Http\Controllers;

use App\Features\CompanyManagement\Http\Resources\CompanyInvitationResource;
use App\Features\CompanyManagement\Services\CompanyInvitationService;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * UserInvitationController
 *
 * Handles API endpoints for users to view and respond to invitations.
 * These are the endpoints shown in the navbar notifications.
 */
class UserInvitationController extends Controller
{
    public function __construct(
        private CompanyInvitationService $invitationService
    ) {}

    /**
     * GET /api/me/invitations
     * Get all invitations for the current user
     */
    public function index(Request $request): JsonResponse
    {
        $userId = JWTHelper::getAuthenticatedUser()['id'];
        $pendingOnly = $request->boolean('pending_only', false);

        if ($pendingOnly) {
            $invitations = $this->invitationService->getPendingInvitationsForUser($userId);
        } else {
            $invitations = $this->invitationService->getInvitationsForUser($userId);
        }

        return response()->json([
            'success' => true,
            'data' => CompanyInvitationResource::collection($invitations),
            'meta' => [
                'total' => $invitations->count(),
                'pending_count' => $this->invitationService->getPendingInvitationsCount($userId),
            ],
        ]);
    }

    /**
     * GET /api/me/invitations/pending-count
     * Get count of pending invitations (for navbar badge)
     */
    public function pendingCount(): JsonResponse
    {
        $userId = JWTHelper::getAuthenticatedUser()['id'];
        $count = $this->invitationService->getPendingInvitationsCount($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * POST /api/me/invitations/{id}/accept
     * Accept an invitation
     */
    public function accept(string $id): JsonResponse
    {
        $userId = JWTHelper::getAuthenticatedUser()['id'];

        $invitation = $this->invitationService->acceptInvitation($id, $userId);

        return response()->json([
            'success' => true,
            'message' => '¡Invitación aceptada! Ahora eres agente de ' . $invitation->company->name,
            'data' => new CompanyInvitationResource($invitation),
        ]);
    }

    /**
     * POST /api/me/invitations/{id}/reject
     * Reject an invitation
     */
    public function reject(string $id): JsonResponse
    {
        $userId = JWTHelper::getAuthenticatedUser()['id'];

        $invitation = $this->invitationService->rejectInvitation($id, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Invitación rechazada',
            'data' => new CompanyInvitationResource($invitation),
        ]);
    }
}
