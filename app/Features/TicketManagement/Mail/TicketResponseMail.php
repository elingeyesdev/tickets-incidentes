<?php

namespace App\Features\TicketManagement\Mail;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Header;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Ticket Response Email
 *
 * Email enviado al usuario cuando un agente responde a su ticket.
 * Implementa email threading (Gmail, Outlook, etc.) para que todas las respuestas
 * aparezcan en el mismo thread/conversación.
 *
 * Headers especiales para threading:
 * - Message-ID: Único por ticket (persistente)
 * - In-Reply-To: Apunta al ticket original
 * - References: Lista completa del thread
 *
 * Incluye:
 * - Respuesta actual del agente (en la parte superior)
 * - Historial completo de respuestas (hacia abajo)
 * - Información del agente que respondió
 * - Estado actual del ticket
 * - Botón "Ver en Helpdesk"
 */
class TicketResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $displayName;
    public string $agentDisplayName;
    public string $ticketViewUrl;
    public Collection $conversationHistory;
    public string $ticketStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ticket $ticket,
        public TicketResponse $response,
        public User $recipient,
        public User $agent,
    ) {
        // Nombre del destinatario para mostrar
        $this->displayName = $recipient->profile
            ? $recipient->profile->first_name . ' ' . $recipient->profile->last_name
            : $recipient->email;

        // Nombre del agente que respondió
        $this->agentDisplayName = $agent->profile
            ? $agent->profile->first_name . ' ' . $agent->profile->last_name
            : $agent->email;

        // URL para ver el ticket en la plataforma
        $this->ticketViewUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/tickets/' . $ticket->id;

        // Construir historial de conversación (todas las respuestas del ticket)
        $this->conversationHistory = $this->buildConversationHistory();

        // Estado legible del ticket
        $this->ticketStatus = $this->getReadableTicketStatus();
    }

    /**
     * Construye el historial de conversación para mostrar en el email
     *
     * @return Collection de respuestas con datos de autor
     */
    private function buildConversationHistory(): Collection
    {
        // Obtener todas las respuestas del ticket, ordenadas por fecha de creación
        $allResponses = $this->ticket->responses()
            ->with('author')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mapear para pasar al template
        return $allResponses->map(function (TicketResponse $resp) {
            $author = $resp->author;
            $authorName = $author->profile
                ? $author->profile->first_name . ' ' . $author->profile->last_name
                : $author->email;

            $authorRole = $resp->isFromAgent() ? 'Agent' : 'Customer';

            return [
                'id' => $resp->id,
                'content' => $resp->content,
                'author_name' => $authorName,
                'author_email' => $author->email,
                'author_role' => $authorRole,
                'author_type' => $resp->author_type->value,
                'created_at' => $resp->created_at,
                'created_at_formatted' => $resp->created_at->format('M d, Y \a\t H:i'),
                'is_current_response' => $resp->id === $this->response->id,
                'is_from_agent' => $resp->isFromAgent(),
            ];
        });
    }

    /**
     * Obtiene el estado del ticket en formato legible
     *
     * @return string
     */
    private function getReadableTicketStatus(): string
    {
        return match ($this->ticket->status->value) {
            'open' => 'Open',
            'pending' => 'Pending (Awaiting your response)',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => $this->ticket->status->value,
        };
    }

    /**
     * Get the message envelope.
     *
     * Subject incluye el código del ticket para threading correcto
     * en clientes como Gmail, Outlook, etc.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: [' . $this->ticket->ticket_code . '] ' . $this->ticket->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticketing.ticket-response',
            text: 'emails.ticketing.ticket-response-text',
            with: [
                'ticket' => $this->ticket,
                'response' => $this->response,
                'recipient' => $this->recipient,
                'agent' => $this->agent,
                'displayName' => $this->displayName,
                'agentDisplayName' => $this->agentDisplayName,
                'ticketViewUrl' => $this->ticketViewUrl,
                'conversationHistory' => $this->conversationHistory,
                'ticketStatus' => $this->ticketStatus,
            ],
        );
    }

    /**
     * Get the message headers.
     *
     * Implementa email threading via Message-ID, In-Reply-To y References.
     * Esto permite que clientes como Gmail, Outlook, Apple Mail agrupen
     * automáticamente todos los emails del ticket en el mismo thread/conversación.
     */
    public function headers(): Headers
    {
        // Message-ID PERSISTENTE - todos los emails del mismo ticket tienen el MISMO ID
        // Esto es lo que permite a Gmail/Outlook agruparlos en UN SOLO THREAD
        $messageId = 'ticket-' . $this->ticket->id . '@helpdesk.com';

        // References: Lista de todos los IDs en la cadena (RFC 5322)
        $references = [$messageId];

        // Headers personalizados para threading + auditoría
        $textHeaders = [
            'In-Reply-To' => '<' . $messageId . '>',
            'X-Ticket-ID' => (string) $this->ticket->id,
            'X-Ticket-Code' => $this->ticket->ticket_code,
            'X-Response-ID' => (string) $this->response->id,
            'X-Agent-ID' => (string) $this->agent->id,
        ];

        return new Headers(
            messageId: $messageId,
            references: $references,
            text: $textHeaders,
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
