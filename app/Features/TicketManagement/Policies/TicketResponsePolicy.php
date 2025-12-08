<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

class TicketResponsePolicy
{
    /**
     * Crear respuesta: creador del ticket, agent o company admin de la compañía.
     * Usa el rol ACTIVO para determinar la empresa.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // Creador puede responder
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent o Company Admin con rol ACTIVO puede responder
        // Solo si el rol activo es AGENT o COMPANY_ADMIN y pertenece a la empresa del ticket
        $activeRole = JWTHelper::getActiveRoleCode();
        if (!in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
            return false;
        }
        
        $companyId = JWTHelper::getActiveCompanyId();
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Ver respuestas: creador del ticket, agent o company admin de la compañía.
     * Usa el rol ACTIVO para determinar la empresa.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent o Company Admin con rol ACTIVO puede ver
        $activeRole = JWTHelper::getActiveRoleCode();
        if (!in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
            return false;
        }
        
        $companyId = JWTHelper::getActiveCompanyId();
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Actualizar respuesta: solo autor dentro de 30 minutos.
     */
    public function update(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }

    /**
     * Eliminar respuesta: solo autor dentro de 30 minutos.
     */
    public function delete(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }
}
