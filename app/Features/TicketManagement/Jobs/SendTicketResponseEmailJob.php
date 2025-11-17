<?php

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Mail\TicketResponseMail;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Send Ticket Response Email Job
 *
 * Encolado cuando un agente responde a un ticket.
 * Envía un email al usuario notificando la respuesta del agente.
 *
 * Configuración:
 * - Queue: 'emails' (cola específica para emails)
 * - Retries: 3 intentos antes de fallar
 * - Timeout: 30 segundos por ejecución
 */
class SendTicketResponseEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de reintentos antes de fallar
     */
    public int $tries = 3;

    /**
     * Timeout en segundos para ejecutar el job
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ticket $ticket,
        public TicketResponse $response,
        public User $recipient,
        public User $agent,
    ) {
        // Asignar el job a la cola 'emails' (específica para notificaciones de email)
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     *
     * Envía el email al usuario del ticket notificando la respuesta del agente.
     */
    public function handle(): void
    {
        \Log::debug('SendTicketResponseEmailJob: Starting email send', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'response_id' => $this->response->id,
            'recipient_email' => $this->recipient->email,
            'agent_id' => $this->agent->id,
        ]);

        try {
            // Crear el email con threading headers y historial de conversación
            $mail = new TicketResponseMail(
                $this->ticket,
                $this->response,
                $this->recipient,
                $this->agent,
            );

            // Enviar email al usuario del ticket
            Mail::to($this->recipient->email)->send($mail);

            \Log::info('SendTicketResponseEmailJob: Email sent successfully', [
                'ticket_id' => $this->ticket->id,
                'recipient_email' => $this->recipient->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('SendTicketResponseEmailJob: Exception during send', [
                'ticket_id' => $this->ticket->id,
                'recipient_email' => $this->recipient->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que Laravel intente reintentar
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * Se ejecuta cuando el job falla después de todos los reintentos.
     * Registra el error para auditoría y debugging.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('SendTicketResponseEmailJob: Job failed after all retries', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'response_id' => $this->response->id,
            'recipient_email' => $this->recipient->email,
            'agent_id' => $this->agent->id,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
        ]);
    }
}