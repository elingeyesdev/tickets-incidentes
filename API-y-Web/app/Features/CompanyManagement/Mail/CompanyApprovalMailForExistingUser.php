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
 * Company Approval Mail For Existing User
 *
 * Email enviado a usuarios existentes cuando su solicitud de empresa es aprobada.
 * No incluye password temporal ya que el usuario ya tiene acceso al sistema.
 */
class CompanyApprovalMailForExistingUser extends Mailable
{
    use Queueable, SerializesModels;

    public string $dashboardUrl;
    public string $displayName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Company $company,
        public User $user
    ) {
        // Generar URL del dashboard de la empresa
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/empresa/dashboard';

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
            subject: 'Solicitud de Empresa Aprobada - Helpdesk',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.company.approval-existing-user',
            text: 'emails.company.approval-existing-user-text',
            with: [
                'company' => $this->company,
                'user' => $this->user,
                'displayName' => $this->displayName,
                'dashboardUrl' => $this->dashboardUrl,
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
