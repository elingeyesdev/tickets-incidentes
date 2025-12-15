<?php

namespace App\Features\Authentication\Jobs;

use App\Features\Authentication\Mail\PasswordResetMail;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Send Password Reset Email Job
 *
 * Job asíncrono para enviar email de reset de contraseña.
 * Se ejecuta en la cola 'emails'.
 */
class SendPasswordResetEmailJob implements ShouldQueue
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
        public string $resetToken,
        public string $resetCode
    ) {
        // Asignar a cola específica
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Enviar email con token y código
        Mail::to($this->user->email)->send(
            new PasswordResetMail(
                $this->user,
                $this->resetToken,
                $this->resetCode
            )
        );
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        // Log del error
        \Log::error('Failed to send password reset email', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'error' => $exception->getMessage(),
        ]);
    }
}