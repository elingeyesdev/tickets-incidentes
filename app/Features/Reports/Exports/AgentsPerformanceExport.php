<?php

declare(strict_types=1);

namespace App\Features\Reports\Exports;

use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Agents Performance Export for Excel
 */
class AgentsPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private string $companyId;

    public function __construct(string $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection()
    {
        return User::whereHas('userRoles', function ($q) {
            $q->where('company_id', $this->companyId)->where('role_code', 'AGENT');
        })->with('profile')->get();
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Email',
            'Tickets Asignados (Total)',
            'Tickets Activos',
            'Tickets Resueltos',
            'Resueltos Hoy',
            'Tasa Resolución (%)',
            'Miembro Desde',
        ];
    }

    public function map($agent): array
    {
        $total = Ticket::where('owner_agent_id', $agent->id)->count();
        $active = Ticket::where('owner_agent_id', $agent->id)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])->count();
        $resolved = Ticket::where('owner_agent_id', $agent->id)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])->count();
        $today = Ticket::where('owner_agent_id', $agent->id)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->whereDate('updated_at', now()->today())->count();
        $rate = $total > 0 ? round(($resolved / $total) * 100) : 0;

        return [
            $agent->profile?->display_name ?? $agent->email,
            $agent->email,
            $total,
            $active,
            $resolved,
            $today,
            $rate . '%',
            $agent->created_at?->format('d/m/Y') ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '28A745'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Rendimiento Agentes';
    }
}
