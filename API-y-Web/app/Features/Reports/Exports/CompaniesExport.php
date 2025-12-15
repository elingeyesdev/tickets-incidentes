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
 * Companies Export for Excel
 * 
 * Exports all companies with their statistics to Excel format.
 */
class CompaniesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private ?string $status;

    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    /**
     * Get the collection of companies to export
     */
    public function collection()
    {
        $query = Company::with(['industry'])
            ->withCount(['tickets'])
            ->withCount(['userRoles as agents_count' => function ($query) {
                $query->where('role_code', 'AGENT')->where('is_active', true);
            }]);
        
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
            'Código',
            'Nombre',
            'Razón Social',
            'Email Soporte',
            'Teléfono',
            'Industria',
            'Estado',
            'Agentes',
            'Tickets',
            'Fecha Creación',
        ];
    }

    /**
     * Map each company row to Excel columns
     */
    public function map($company): array
    {
        return [
            $company->company_code ?? 'N/A',
            $company->name,
            $company->legal_name ?? 'N/A',
            $company->support_email ?? 'N/A',
            $company->support_phone ?? 'N/A',
            $company->industry?->name ?? 'N/A',
            $this->translateStatus($company->status),
            $company->agents_count ?? 0,
            $company->tickets_count ?? 0,
            $company->created_at?->format('d/m/Y H:i') ?? 'N/A',
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
                    'color' => ['rgb' => '007BFF'],
                ],
            ],
        ];
    }

    /**
     * Set the worksheet title
     */
    public function title(): string
    {
        return 'Empresas';
    }

    /**
     * Translate status to Spanish
     */
    private function translateStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Activa',
            'suspended' => 'Suspendida',
            'inactive' => 'Inactiva',
            default => $status ?? 'Desconocido',
        };
    }
}
