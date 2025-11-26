<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Mail;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ticket Reminder Email
 *
 * Email enviado al creador del ticket cuando un agente envía un recordatorio.
 * El propósito es notificar al usuario sobre el estado de su ticket y
 * animarlo a que revise si hay actualizaciones o responda si es necesario.
 *
 * Incluye:
 * - Información básica del ticket
 * - Estado actual del ticket
 * - Botón "Ver Ticket" que redirige al helpdesk
 */
class TicketReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $displayName;
    public string $ticketViewUrl;
    public string $ticketStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ticket $ticket
    ) {
        // Nombre del destinatario para mostrar
        $creator = $this->ticket->creator;
        $this->displayName = $creator->profile
            ? $creator->profile->first_name . ' ' . $creator->profile->last_name
            : $creator->email;

        // URL para ver el ticket en la plataforma
        $this->ticketViewUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/tickets/' . $ticket->id;

        // Estado legible del ticket
        $this->ticketStatus = $this->getReadableTicketStatus();
    }

    /**
     * Obtiene el estado del ticket en formato legible
     *
     * @return string
     */
    private function getReadableTicketStatus(): string
    {
        return match ($this->ticket->status->value) {
            'open' => 'Abierto',
            'pending' => 'Pendiente (Esperando tu respuesta)',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            default => $this->ticket->status->value,
        };
    }

    /**
     * Get the message envelope.
     *
     * Subject incluye el código del ticket para facilitar el tracking
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: [' . $this->ticket->ticket_code . '] ' . $this->ticket->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticketing.ticket-reminder',
            text: 'emails.ticketing.ticket-reminder-text',
            with: [
                'ticket' => $this->ticket,
                'displayName' => $this->displayName,
                'ticketViewUrl' => $this->ticketViewUrl,
                'ticketStatus' => $this->ticketStatus,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
