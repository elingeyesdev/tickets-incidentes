<?php

namespace App\Features\Authentication\Mail;

use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Mail
 *
 * Email enviado al usuario para restablecer su contraseña.
 * Contiene un link con token que expira en 1 hora.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $displayName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $resetToken
    ) {
        // Generar URL de reset
        $this->resetUrl = config('app.frontend_url', config('app.url'))
            . "/reset-password?token={$resetToken}";

        // Nombre para mostrar
        $this->displayName = $user->profile
            ? $user->profile->first_name . ' ' . $user->profile->last_name
            : $user->email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔑 Restablece tu contraseña - Helpdesk',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.reset-password',
            text: 'emails.auth.reset-password-text',
            with: [
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'displayName' => $this->displayName,
                'expiresInHours' => 1,
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