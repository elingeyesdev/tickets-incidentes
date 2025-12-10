<?php

declare(strict_types=1);

namespace App\Features\Reports\Exports;

use App\Features\CompanyManagement\Models\CompanyRequest;
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
        $query = CompanyRequest::with(['reviewedBy']);
        
        if ($this->status) {
            $query->where('status', $this->status);
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
            'Email Admin',
            'Nombre Admin',
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
    public function map($request): array
    {
        return [
            substr($request->id, 0, 8) . '...',
            $request->company_name,
            $request->company_legal_name ?? 'N/A',
            $request->admin_email,
            $request->admin_name ?? 'N/A',
            $request->industry_name ?? 'N/A',
            $this->translateStatus($request->status),
            $request->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $request->reviewed_at?->format('d/m/Y H:i') ?? 'Pendiente',
            $request->reviewedBy?->email ?? 'N/A',
            $request->rejection_reason ?? '-',
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
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            default => $status ?? 'Desconocido',
        };
    }
}
