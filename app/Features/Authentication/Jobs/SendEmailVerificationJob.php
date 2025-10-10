<?php

namespace App\Features\Authentication\Jobs;

use App\Features\Authentication\Mail\EmailVerificationMail;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Send Email Verification Job
 *
 * Job asíncrono para enviar email de verificación de cuenta.
 * Se ejecuta en la cola 'emails'.
 */
class SendEmailVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Timeout en segundos
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $verificationToken
    ) {
        // Asignar a cola específica
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Verificar que el usuario aún no haya verificado su email
        if ($this->user->hasVerifiedEmail()) {
            return;
        }

        // Enviar email
        Mail::to($this->user->email)->send(
            new EmailVerificationMail(
                $this->user,
                $this->verificationToken
            )
        );
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        // Log del error
        \Log::error('Failed to send email verification', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
