<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

/**
 * TicketPolicy - Autorización para gestión de tickets
 *
 * Reglas:
 * - Solo USER puede crear tickets
 * - Creador puede ver/editar su ticket si está OPEN
 * - Agent/Admin pueden ver/editar tickets de su compañía
 * - Solo COMPANY_ADMIN puede eliminar tickets CLOSED
 * - AGENT puede resolver y asignar tickets
 * - Creador puede cerrar si está RESOLVED, AGENT puede cerrar cualquiera
 * - Creador puede reabrir (con restricción de 30 días en Rule)
 */
class TicketPolicy
{
    /**
     * Solo USER puede crear tickets.
     */
    public function create(User $user): bool
    {
        return JWTHelper::hasRoleFromJWT('USER');
    }

    /**
     * Ver ticket: creador, agent/admin de la misma compañía, o PLATFORM_ADMIN.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // PLATFORM_ADMIN puede ver todos los tickets
        if ($user->hasRole('PLATFORM_ADMIN')) {
            return true;
        }

        // Creador puede ver siempre
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent/Admin pueden ver tickets de su compañía
        if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
            return true;
        }

        if ($user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Actualizar ticket: creador (solo OPEN) o agent de la compañía.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Creador solo puede editar si está OPEN
        if ($ticket->created_by_user_id === $user->id) {
            return $ticket->status === TicketStatus::OPEN;
        }

        // Agent/Admin pueden editar tickets de su compañía
        if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
            return true;
        }

        if ($user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
            return true;
        }

        return false;
    }

    /**
     * Eliminar ticket: solo COMPANY_ADMIN y ticket debe estar cerrado.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)
            && $ticket->status === TicketStatus::CLOSED;
    }

    /**
     * Resolver ticket: solo AGENT de la compañía.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->hasRoleInCompany('AGENT', $ticket->company_id);
    }

    /**
     * Cerrar ticket: AGENT puede cerrar cualquiera, USER solo si está resolved.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        // Agent de la compañía puede cerrar cualquiera
        if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
            return true;
        }

        // Creador solo puede cerrar si está RESOLVED
        return $ticket->created_by_user_id === $user->id
            && $ticket->status === TicketStatus::RESOLVED;
    }

    /**
     * Reabrir ticket: creador o agent de la compañía.
     * Creador solo puede reabrir en los primeros 30 días después del cierre.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        // Creador puede reabrir (con restricción de 30 días)
        if ($ticket->created_by_user_id === $user->id) {
            // Si ticket está CLOSED, verificar la restricción de 30 días
            if ($ticket->status === TicketStatus::CLOSED && $ticket->closed_at) {
                $daysSinceClosed = Carbon::parse($ticket->closed_at)->diffInDays(Carbon::now());
                if ($daysSinceClosed > 30) {
                    return false; // No puede reabrir después de 30 días
                }
            }
            return true;
        }

        // Agent puede reabrir sin restricciones
        return $user->hasRoleInCompany('AGENT', $ticket->company_id);
    }

    /**
     * Asignar ticket: AGENT o COMPANY_ADMIN de la compañía.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasRoleInCompany('AGENT', $ticket->company_id)
            || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
    }

    /**
     * Enviar recordatorio: AGENT o COMPANY_ADMIN de la compañía.
     */
    public function sendReminder(User $user, Ticket $ticket): bool
    {
        return $user->hasRoleInCompany('AGENT', $ticket->company_id)
            || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
    }
}
