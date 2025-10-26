<?php

namespace App\Features\CompanyManagement\Mail;

use App\Features\CompanyManagement\Models\CompanyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Company Rejection Mail
 *
 * Email enviado cuando una solicitud de empresa es rechazada.
 * INCLUYE la razón del rechazo para transparencia.
 */
class CompanyRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $displayName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CompanyRequest $request,
        public string $reason
    ) {
        // Nombre para mostrar (usamos email ya que no tenemos first_name/last_name en CompanyRequest)
        $this->displayName = $request->admin_email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Empresa Rechazada - Helpdesk',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.company.rejection',
            text: 'emails.company.rejection-text',
            with: [
                'request' => $this->request,
                'displayName' => $this->displayName,
                'reason' => $this->reason,
                'supportEmail' => config('mail.from.address'),
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
