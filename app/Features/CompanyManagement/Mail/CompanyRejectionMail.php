<?php

namespace App\Features\CompanyManagement\Mail;

use App\Features\CompanyManagement\Models\Company;
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
 * 
 * NOTA: Ahora recibe Company (con status='rejected') en lugar de CompanyRequest.
 */
class CompanyRejectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $displayName;
    public string $companyName;
    public string $adminEmail;
    public string $requestCode;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Company $company,
        public string $reason
    ) {
        $onboardingDetails = $company->onboardingDetails;

        // Nombre para mostrar: usar email del solicitante desde onboarding details
        $this->displayName = $onboardingDetails?->submitter_email
            ?? $company->support_email
            ?? 'Usuario';

        // Extraer valores para compatibilidad con vistas
        $this->companyName = $company->name;
        $this->adminEmail = $onboardingDetails?->submitter_email ?? $company->support_email ?? '';
        $this->requestCode = $onboardingDetails?->request_code ?? $company->company_code ?? '';
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
        // Crear objeto con propiedades compatibles para la vista
        $requestAlias = (object) [
            'company_name' => $this->companyName,
            'admin_email' => $this->adminEmail,
            'request_code' => $this->requestCode,
            'name' => $this->companyName,
        ];

        return new Content(
            view: 'emails.company.rejection',
            text: 'emails.company.rejection-text',
            with: [
                'company' => $this->company,
                'displayName' => $this->displayName,
                'reason' => $this->reason,
                'supportEmail' => config('mail.from.address'),
                // 'request' como objeto para compatibilidad con vistas existentes
                'request' => $requestAlias,
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
