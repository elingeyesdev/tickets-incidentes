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
 * Email enviado al usuario cuando solicita reset de contraseña.
 * Incluye:
 * - Token (32 caracteres) - para links/requests
 * - Código (6 dígitos) - para SMS/entrada manual
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
        public string $resetToken,
        public string $resetCode
    ) {
        // Generar URL de reset con token
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $this->resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($resetToken);

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
            view: 'emails.authentication.password-reset',
            text: 'emails.authentication.password-reset-text',
            with: [
                'user' => $this->user,
                'resetToken' => $this->resetToken,
                'resetCode' => $this->resetCode,
                'resetUrl' => $this->resetUrl,
                'displayName' => $this->displayName,
                'expiresInHours' => 24,
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