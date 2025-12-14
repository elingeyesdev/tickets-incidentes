<?php

declare(strict_types=1);

namespace App\Features\Reports\Exports;

use App\Features\TicketManagement\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * User Tickets Export for Excel
 * 
 * Exports all tickets created by a user to Excel format.
 */
class UserTicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private string $userId;
    private ?string $status;
    private ?string $priority;
    private ?string $dateFrom;
    private ?string $dateTo;
    private ?string $companyId;
    private ?string $categoryId;

    public function __construct(
        string $userId,
        ?string $status = null,
        ?string $priority = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $companyId = null,
        ?string $categoryId = null
    ) {
        $this->userId = $userId;
        $this->status = $status;
        $this->priority = $priority;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->companyId = $companyId;
        $this->categoryId = $categoryId;
    }

    /**
     * Get the collection of tickets to export
     */
    public function collection()
    {
        $query = Ticket::with(['company', 'category'])
            ->where('created_by_user_id', $this->userId);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->priority) {
            $query->where('priority', $this->priority);
        }

        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
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
            'Asunto',
            'Empresa',
            'Categoría',
            'Prioridad',
            'Estado',
            'Fecha Creación',
            'Última Actualización',
            'Fecha Resolución',
        ];
    }

    /**
     * Map each ticket row to Excel columns
     */
    public function map($ticket): array
    {
        return [
            $ticket->ticket_code ?? 'N/A',
            $ticket->title,
            $ticket->company?->name ?? 'N/A',
            $ticket->category?->name ?? 'Sin categoría',
            $this->translatePriority($ticket->priority?->value ?? $ticket->priority),
            $this->translateStatus($ticket->status?->value ?? $ticket->status),
            $ticket->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $ticket->updated_at?->format('d/m/Y H:i') ?? 'N/A',
            $ticket->resolved_at?->format('d/m/Y H:i') ?? '-',
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
                    'color' => ['rgb' => '17A2B8'],
                ],
            ],
        ];
    }

    /**
     * Set the worksheet title
     */
    public function title(): string
    {
        return 'Mis Tickets';
    }

    /**
     * Translate status to Spanish
     */
    private function translateStatus(?string $status): string
    {
        return match (strtoupper($status ?? '')) {
            'OPEN' => 'Abierto',
            'PENDING' => 'Pendiente',
            'RESOLVED' => 'Resuelto',
            'CLOSED' => 'Cerrado',
            default => $status ?? 'Desconocido',
        };
    }

    /**
     * Translate priority to Spanish
     */
    private function translatePriority(?string $priority): string
    {
        return match (strtoupper($priority ?? '')) {
            'HIGH' => 'Alta',
            'MEDIUM' => 'Media',
            'LOW' => 'Baja',
            default => $priority ?? 'N/A',
        };
    }
}
