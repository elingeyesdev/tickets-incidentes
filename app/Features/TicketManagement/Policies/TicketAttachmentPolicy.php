<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

/**
 * TicketAttachmentPolicy - Autorización para gestión de adjuntos en tickets
 *
 * Reglas:
 * - Subir a ticket: creador del ticket o agent de la compañía
 * - Subir a respuesta: solo autor de la respuesta dentro de 30 minutos
 * - Ver adjuntos: creador del ticket o agent de la compañía
 * - Eliminar adjunto: solo uploader dentro de 30 minutos
 */
class TicketAttachmentPolicy
{
    /**
     * Subir attachment a ticket: creador del ticket, agent o company admin de la compañía.
     */
    public function upload(User $user, Ticket $ticket): bool
    {
        // Creador puede subir
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent o Company Admin de la compañía puede subir
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        if (!$companyId) {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        }
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Subir attachment a response específica: solo autor de la response dentro de 30 min.
     */
    public function uploadToResponse(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor de la response
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }

    /**
     * Ver attachments: creador del ticket, agent o company admin de la compañía.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent o Company Admin de la compañía puede ver
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        if (!$companyId) {
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        }
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Eliminar attachment: solo uploader dentro de 30 minutos.
     */
    public function delete(User $user, TicketAttachment $attachment): bool
    {
        // Debe ser el uploader
        if ($attachment->uploaded_by_user_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceUpload = Carbon::parse($attachment->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceUpload <= 30;
    }
}
