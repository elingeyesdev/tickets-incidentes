<?php

namespace App\Features\Authentication\Mail;

use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email Verification Mail
 *
 * Email enviado al usuario para verificar su cuenta.
 * Incluye:
 * - Token (64 caracteres) - para links/requests
 * - Código (6 dígitos) - para entrada manual
 */
class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;
    public string $displayName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $verificationToken,
        public string $verificationCode
    ) {
        // Generar URL de verificación con token
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $this->verificationUrl = $frontendUrl . '/verify-email?token=' . urlencode($verificationToken);

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
            subject: '🔐 Verifica tu cuenta - Helpdesk',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verify-email',
            text: 'emails.auth.verify-email-text',
            with: [
                'user' => $this->user,
                'verificationToken' => $this->verificationToken,
                'verificationCode' => $this->verificationCode,
                'verificationUrl' => $this->verificationUrl,
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
