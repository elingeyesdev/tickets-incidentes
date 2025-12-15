<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

/**
 * TicketRatingPolicy - Autorización para gestión de calificaciones en tickets
 *
 * Reglas:
 * - Crear rating: solo creador del ticket y debe estar RESOLVED o CLOSED
 * - Actualizar rating: solo creador dentro de 24 horas
 * - Ver rating: creador del ticket o agent/admin de la compañía
 */
class TicketRatingPolicy
{
    /**
     * Crear rating: solo creador del ticket y debe estar resolved/closed.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by_user_id === $user->id
            && in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED]);
    }

    /**
     * Actualizar rating: solo creador dentro de 24 horas.
     */
    public function update(User $user, TicketRating $rating): bool
    {
        // Debe ser el creador
        if ($rating->rated_by_user_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 24 horas
        $hoursSinceCreation = Carbon::parse($rating->created_at)->diffInHours(Carbon::now());
        return $hoursSinceCreation <= 24;
    }

    /**
     * Ver rating: creador del ticket o agent de la compañía.
     * Usa el rol ACTIVO para determinar la empresa.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent/Admin con rol ACTIVO puede ver
        $activeRole = JWTHelper::getActiveRoleCode();
        if (!in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
            return false;
        }
        
        $companyId = JWTHelper::getActiveCompanyId();
        return $companyId && $ticket->company_id === $companyId;
    }
}
