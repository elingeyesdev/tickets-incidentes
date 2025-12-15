<?php

namespace App\Features\TicketManagement\Notifications;

use App\Features\TicketManagement\Models\TicketResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification: ResponseNotification
 *
 * Notifica a las partes relevantes cuando se crea una nueva respuesta:
 * - Si USER responde → notificar al AGENT asignado
 * - Si AGENT responde → notificar al USER creador del ticket
 */
class ResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TicketResponse $response
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->response->ticket;
        $authorType = $this->response->author_type->value;
        $authorName = $this->response->author->profile->full_name ?? $this->response->author->email;
        $notifiableName = $notifiable->profile->full_name ?? $notifiable->email;

        return (new MailMessage)
            ->subject("Nueva respuesta en ticket {$ticket->ticket_code}")
            ->greeting("Hola {$notifiableName}!")
            ->line("Ha recibido una nueva respuesta de {$authorName} ({$authorType}):")
            ->line($this->response->content)
            ->action('Ver Ticket', url("/tickets/{$ticket->ticket_code}"))
            ->line('Gracias por usar nuestro sistema de tickets.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'response_id' => $this->response->id,
            'ticket_id' => $this->response->ticket_id,
            'ticket_code' => $this->response->ticket->ticket_code,
            'author_type' => $this->response->author_type->value,
            'author_name' => $this->response->author->profile->full_name ?? $this->response->author->email,
            'content_preview' => substr($this->response->content, 0, 100),
        ];
    }
}
