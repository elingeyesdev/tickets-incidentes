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
 * Contiene un link con token que expira en 24 horas.
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
        public string $verificationToken
    ) {
        // Generar URL de verificación
        $this->verificationUrl = config('app.frontend_url', config('app.url'))
            . "/verify-email?token={$verificationToken}&user_id={$user->id}";

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