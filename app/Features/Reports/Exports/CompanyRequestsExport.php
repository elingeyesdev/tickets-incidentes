<?php

declare(strict_types=1);

namespace App\Features\Reports\Exports;

use App\Features\CompanyManagement\Models\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Company Requests Export for Excel
 * 
 * Exports all company registration requests to Excel format.
 * 
 * ARQUITECTURA NORMALIZADA:
 * - Ahora usa Company con diferentes status en lugar de CompanyRequest
 * - Los datos de solicitud vienen de Company->onboardingDetails
 */
class CompanyRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private ?string $status;

    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    /**
     * Get the collection of requests to export
     */
    public function collection()
    {
        $query = Company::withAllStatuses()
            ->with(['onboardingDetails.reviewer', 'industry']);

        if ($this->status) {
            // Mapear status del request al status de Company
            $mappedStatus = match ($this->status) {
                'approved' => 'active',
                default => $this->status,
            };
            $query->where('status', $mappedStatus);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nombre Empresa',
            'Razón Social',
            'Email Solicitante',
            'Industria',
            'Estado',
            'Fecha Solicitud',
            'Fecha Revisión',
            'Revisado Por',
            'Motivo Rechazo',
        ];
    }

    /**
     * Map each request row to Excel columns
     */
    public function map($company): array
    {
        $onboardingDetails = $company->onboardingDetails;

        return [
            substr($company->id, 0, 8) . '...',
            $company->name,
            $company->legal_name ?? 'N/A',
            $onboardingDetails?->submitter_email ?? $company->support_email,
            $company->industry?->name ?? 'N/A',
            $this->translateStatus($company->status),
            $company->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $onboardingDetails?->reviewed_at?->format('d/m/Y H:i') ?? 'Pendiente',
            $onboardingDetails?->reviewer?->email ?? 'N/A',
            $onboardingDetails?->rejection_reason ?? '-',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '6F42C1'], // Purple
                ],
            ],
        ];
    }

    /**
     * Set the worksheet title
     */
    public function title(): string
    {
        return 'Solicitudes';
    }

    /**
     * Translate status to Spanish
     */
    private function translateStatus(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'active' => 'Aprobada',
            'rejected' => 'Rechazada',
            'suspended' => 'Suspendida',
            default => $status ?? 'Desconocido',
        };
    }
}
