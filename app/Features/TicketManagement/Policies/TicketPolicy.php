<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

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
     * Ver ticket: creador o agent/admin de la misma compañía.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Creador puede ver siempre
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent/Admin pueden ver tickets de su compañía
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
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
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Eliminar ticket: solo COMPANY_ADMIN y ticket debe estar cerrado.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId
            && $ticket->company_id === $companyId
            && $ticket->status === TicketStatus::CLOSED;
    }

    /**
     * Resolver ticket: solo AGENT de la compañía.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Cerrar ticket: AGENT puede cerrar cualquiera, USER solo si está resolved.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        // Agent de la compañía puede cerrar cualquiera
        $agentCompanyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        if ($agentCompanyId && $ticket->company_id === $agentCompanyId) {
            return true;
        }

        // Creador solo puede cerrar si está RESOLVED
        return $ticket->created_by_user_id === $user->id
            && $ticket->status === TicketStatus::RESOLVED;
    }

    /**
     * Reabrir ticket: creador o agent de la compañía.
     * La Rule CanReopenTicket maneja la validación de 30 días.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        // Creador puede reabrir (con restricción de 30 días en Rule)
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent puede reabrir sin restricciones
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Asignar ticket: solo AGENT de la compañía.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        return $companyId && $ticket->company_id === $companyId;
    }
}
