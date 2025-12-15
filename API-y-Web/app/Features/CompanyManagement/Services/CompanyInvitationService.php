<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyInvitation;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Features\UserManagement\Services\RoleService;
use App\Shared\Enums\UserStatus;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CompanyInvitationService
 *
 * Handles the business logic for company invitations.
 * Allows Company Admins to invite users to become agents.
 */
class CompanyInvitationService
{
    public function __construct(
        private RoleService $roleService
    ) {
    }

    /**
     * Create a new invitation for a user to join a company as agent
     *
     * @param string $companyId Company sending the invitation
     * @param string $userId User to invite
     * @param string $invitedBy User creating the invitation (Company Admin)
     * @param string|null $message Optional message
     * @param string $roleCode Role to assign (default: AGENT)
     * @return CompanyInvitation
     * @throws ValidationException
     * @throws NotFoundException
     */
    public function createInvitation(
        string $companyId,
        string $userId,
        string $invitedBy,
        ?string $message = null,
        string $roleCode = 'AGENT'
    ): CompanyInvitation {
        // Validate company exists
        $company = Company::find($companyId);
        if (!$company) {
            throw NotFoundException::resource('Empresa', $companyId);
        }

        // Validate user exists
        $user = User::find($userId);
        if (!$user) {
            throw NotFoundException::resource('Usuario', $userId);
        }

        // Check if user already has this role in the company
        $existingRole = UserRole::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('role_code', $roleCode)
            ->where('is_active', true)
            ->first();

        if ($existingRole) {
            throw ValidationException::withField(
                'user_id',
                'El usuario ya es agente de esta empresa'
            );
        }

        // Check if there's already a pending invitation
        $existingInvitation = CompanyInvitation::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', CompanyInvitation::STATUS_PENDING)
            ->first();

        if ($existingInvitation) {
            throw ValidationException::withField(
                'user_id',
                'Ya existe una invitación pendiente para este usuario'
            );
        }

        // Create the invitation
        $invitation = CompanyInvitation::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_code' => $roleCode,
            'status' => CompanyInvitation::STATUS_PENDING,
            'invited_by' => $invitedBy,
            'message' => $message,
        ]);

        Log::info('[CompanyInvitation] Created invitation', [
            'invitation_id' => $invitation->id,
            'company_id' => $companyId,
            'user_id' => $userId,
            'invited_by' => $invitedBy,
        ]);

        return $invitation->load(['company', 'user', 'inviter']);
    }

    /**
     * Accept an invitation - assigns the role to the user
     *
     * @param string $invitationId
     * @param string $userId User accepting (must match invitation user_id)
     * @return CompanyInvitation
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function acceptInvitation(string $invitationId, string $userId): CompanyInvitation
    {
        $invitation = CompanyInvitation::find($invitationId);

        if (!$invitation) {
            throw NotFoundException::resource('Invitación', $invitationId);
        }

        // Verify the user accepting is the invited user
        if ($invitation->user_id !== $userId) {
            throw ValidationException::withField(
                'user_id',
                'No tienes permiso para responder a esta invitación'
            );
        }

        // Check invitation is still pending
        if (!$invitation->isPending()) {
            throw ValidationException::withField(
                'status',
                'Esta invitación ya fue respondida o cancelada'
            );
        }

        DB::transaction(function () use ($invitation) {
            // Update invitation status
            $invitation->accept();

            // Assign the role using RoleService
            $this->roleService->assignRoleToUser(
                $invitation->user_id,
                $invitation->role_code,
                $invitation->company_id,
                $invitation->invited_by // Assigned by the inviter
            );

            Log::info('[CompanyInvitation] Invitation accepted', [
                'invitation_id' => $invitation->id,
                'user_id' => $invitation->user_id,
                'company_id' => $invitation->company_id,
                'role_code' => $invitation->role_code,
            ]);
        });

        // Refresh AFTER transaction completes (following CompanyRequestService pattern)
        // This is important because the Spatie observer may fail within the transaction,
        // but we still want to return fresh data after the core operation succeeds
        return $invitation->fresh(['company', 'user', 'inviter']);
    }

    /**
     * Reject an invitation
     *
     * @param string $invitationId
     * @param string $userId User rejecting (must match invitation user_id)
     * @return CompanyInvitation
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function rejectInvitation(string $invitationId, string $userId): CompanyInvitation
    {
        $invitation = CompanyInvitation::find($invitationId);

        if (!$invitation) {
            throw NotFoundException::resource('Invitación', $invitationId);
        }

        // Verify the user rejecting is the invited user
        if ($invitation->user_id !== $userId) {
            throw ValidationException::withField(
                'user_id',
                'No tienes permiso para responder a esta invitación'
            );
        }

        // Check invitation is still pending
        if (!$invitation->isPending()) {
            throw ValidationException::withField(
                'status',
                'Esta invitación ya fue respondida o cancelada'
            );
        }

        $invitation->reject();

        Log::info('[CompanyInvitation] Invitation rejected', [
            'invitation_id' => $invitation->id,
            'user_id' => $invitation->user_id,
            'company_id' => $invitation->company_id,
        ]);

        return $invitation->fresh(['company', 'user', 'inviter']);
    }

    /**
     * Cancel an invitation (by Company Admin)
     *
     * @param string $invitationId
     * @param string $companyId Company of the admin (for authorization)
     * @return CompanyInvitation
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function cancelInvitation(string $invitationId, string $companyId): CompanyInvitation
    {
        $invitation = CompanyInvitation::find($invitationId);

        if (!$invitation) {
            throw NotFoundException::resource('Invitación', $invitationId);
        }

        // Verify the company admin owns this invitation
        if ($invitation->company_id !== $companyId) {
            throw ValidationException::withField(
                'company_id',
                'No tienes permiso para cancelar esta invitación'
            );
        }

        // Check invitation is still pending
        if (!$invitation->isPending()) {
            throw ValidationException::withField(
                'status',
                'Esta invitación ya fue respondida o cancelada'
            );
        }

        $invitation->cancel();

        Log::info('[CompanyInvitation] Invitation cancelled', [
            'invitation_id' => $invitation->id,
            'company_id' => $companyId,
        ]);

        return $invitation->fresh(['company', 'user', 'inviter']);
    }

    /**
     * Get all invitations for a company
     *
     * @param string $companyId
     * @param string|null $status Filter by status
     * @return Collection
     */
    public function getInvitationsByCompany(string $companyId, ?string $status = null): Collection
    {
        $query = CompanyInvitation::forCompany($companyId)
            ->with(['user.profile', 'inviter.profile'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get pending invitations for a user (for navbar notifications)
     *
     * @param string $userId
     * @return Collection
     */
    public function getPendingInvitationsForUser(string $userId): Collection
    {
        return CompanyInvitation::forUser($userId)
            ->pending()
            ->with(['company', 'inviter.profile'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all invitations for a user
     *
     * @param string $userId
     * @return Collection
     */
    public function getInvitationsForUser(string $userId): Collection
    {
        return CompanyInvitation::forUser($userId)
            ->with(['company', 'inviter.profile'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get count of pending invitations for a user
     *
     * @param string $userId
     * @return int
     */
    public function getPendingInvitationsCount(string $userId): int
    {
        return CompanyInvitation::forUser($userId)
            ->pending()
            ->count();
    }

    /**
     * Get all agents for a company (active AGENT roles)
     *
     * @param string $companyId
     * @return Collection
     */
    public function getAgentsForCompany(string $companyId): Collection
    {
        return UserRole::where('company_id', $companyId)
            ->where('role_code', 'AGENT')
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->with(['user.profile'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Remove agent role from a user (by Company Admin)
     *
     * @param string $userRoleId The UserRole ID
     * @param string $companyId Company of the admin (for authorization)
     * @return bool
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function removeAgent(string $userRoleId, string $companyId): bool
    {
        $userRole = UserRole::find($userRoleId);

        if (!$userRole) {
            throw NotFoundException::resource('Rol de usuario', $userRoleId);
        }

        // Verify the company admin owns this role
        if ($userRole->company_id !== $companyId) {
            throw ValidationException::withField(
                'company_id',
                'No tienes permiso para remover este agente'
            );
        }

        // Verify it's an AGENT role
        if ($userRole->role_code !== 'AGENT') {
            throw ValidationException::withField(
                'role_code',
                'Solo puedes remover agentes, no otros roles'
            );
        }

        return $this->roleService->removeRoleById($userRoleId, 'Removido por Company Admin');
    }

    /**
     * Search users that can be invited (not already agents of this company)
     *
     * @param string $companyId
     * @param string $search Email or name search
     * @param int $limit
     * @return Collection
     */
    public function searchUsersForInvitation(string $companyId, string $search, int $limit = 10): Collection
    {
        // Get users who are already agents of this company
        $existingAgentIds = UserRole::where('company_id', $companyId)
            ->where('role_code', 'AGENT')
            ->where('is_active', true)
            ->pluck('user_id')
            ->toArray();

        // Get users with pending invitations
        $pendingInvitationUserIds = CompanyInvitation::where('company_id', $companyId)
            ->where('status', CompanyInvitation::STATUS_PENDING)
            ->pluck('user_id')
            ->toArray();

        $excludeIds = array_merge($existingAgentIds, $pendingInvitationUserIds);

        return User::where(function ($query) use ($search) {
            $query->where('email', 'ILIKE', "%{$search}%")
                ->orWhereHas('profile', function ($q) use ($search) {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%");
                });
        })
            ->whereNotIn('id', $excludeIds)
            ->where('status', UserStatus::ACTIVE)
            ->with('profile')
            ->limit($limit)
            ->get();
    }
}
