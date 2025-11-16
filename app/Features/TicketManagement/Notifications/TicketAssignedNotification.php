<?php

namespace App\Features\TicketManagement\Notifications;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification: TicketAssignedNotification
 *
 * Notifica al agente cuando un ticket es asignado a él.
 */
class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket
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
        $agentName = $notifiable->profile->full_name ?? $notifiable->email;

        return (new MailMessage)
            ->subject("Ticket asignado: {$this->ticket->ticket_code}")
            ->greeting("Hola {$agentName}!")
            ->line("Se te ha asignado un nuevo ticket.")
            ->line("**Título:** {$this->ticket->title}")
            ->line("**Código:** {$this->ticket->ticket_code}")
            ->line("**Descripción:** {$this->ticket->description}")
            ->action('Ver Ticket', url("/tickets/{$this->ticket->ticket_code}"))
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
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'ticket_title' => $this->ticket->title,
            'description_preview' => substr($this->ticket->description, 0, 100),
        ];
    }
}
