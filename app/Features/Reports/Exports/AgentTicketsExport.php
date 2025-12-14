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
 * Agent Tickets Export for Excel
 */
class AgentTicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private string $agentId;
    private ?string $status;
    private ?string $priority;
    private ?string $categoryId;
    private ?string $dateFrom;
    private ?string $dateTo;

    public function __construct(
        string $agentId,
        ?string $status = null,
        ?string $priority = null,
        ?string $categoryId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ) {
        $this->agentId = $agentId;
        $this->status = $status;
        $this->priority = $priority;
        $this->categoryId = $categoryId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        $query = Ticket::with(['creator.profile', 'category'])
            ->where('owner_agent_id', $this->agentId)
            ->withCount(['responses', 'attachments']);

        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->priority) {
            $query->where('priority', $this->priority);
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

    public function headings(): array
    {
        return [
            'Código',
            'Título',
            'Creado Por',
            'Categoría',
            'Prioridad',
            'Estado',
            '# Respuestas',
            '# Adjuntos',
            'Fecha Creación',
            'Última Actualización',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_code ?? 'N/A',
            $ticket->title ?? '',
            $ticket->creator?->profile?->display_name ?? $ticket->creator?->email ?? 'N/A',
            $ticket->category?->name ?? 'Sin categoría',
            $this->translatePriority($ticket->priority?->value ?? $ticket->priority),
            $this->translateStatus($ticket->status?->value ?? $ticket->status),
            $ticket->responses_count ?? 0,
            $ticket->attachments_count ?? 0,
            $ticket->created_at?->format('d/m/Y H:i') ?? 'N/A',
            $ticket->updated_at?->format('d/m/Y H:i') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '17A2B8'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Mis Tickets';
    }

    private function translateStatus(?string $status): string
    {
        return match (strtoupper($status ?? '')) {
            'OPEN' => 'Abierto',
            'PENDING' => 'Pendiente',
            'RESOLVED' => 'Resuelto',
            'CLOSED' => 'Cerrado',
            default => $status ?? 'N/A',
        };
    }

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
