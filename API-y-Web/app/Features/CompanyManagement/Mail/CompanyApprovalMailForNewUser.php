<?php

namespace App\Features\CompanyManagement\Mail;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Company Approval Mail For New User
 *
 * Email enviado a nuevos usuarios cuando su solicitud de empresa es aprobada.
 * INCLUYE password temporal que deben cambiar en el primer login.
 */
class CompanyApprovalMailForNewUser extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;
    public string $displayName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Company $company,
        public User $user,
        public string $temporaryPassword
    ) {
        // Generar URL de login
        $this->loginUrl = config('app.frontend_url', config('app.url')) . '/login';

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
            subject: 'Bienvenido a Helpdesk - Solicitud de Empresa Aprobada',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.company.approval-new-user',
            text: 'emails.company.approval-new-user-text',
            with: [
                'company' => $this->company,
                'user' => $this->user,
                'displayName' => $this->displayName,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $this->loginUrl,
                'expiresInDays' => 7,
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
