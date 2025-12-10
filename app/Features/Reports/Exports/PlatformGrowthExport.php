<?php

declare(strict_types=1);

namespace App\Features\Reports\Exports;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Platform Growth Export for Excel
 * 
 * Exports platform growth statistics to Excel format.
 * Creates multiple sheets: Monthly Data and Summary.
 */
class PlatformGrowthExport implements WithMultipleSheets
{
    private int $months;

    public function __construct(int $months = 6)
    {
        $this->months = $months;
    }

    /**
     * Return array of sheets
     */
    public function sheets(): array
    {
        return [
            new GrowthMonthlySheet($this->months),
            new GrowthSummarySheet(),
        ];
    }
}

/**
 * Monthly Growth Data Sheet
 */
class GrowthMonthlySheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    private int $months;

    public function __construct(int $months)
    {
        $this->months = $months;
    }

    public function array(): array
    {
        $startDate = now()->subMonths($this->months)->startOfMonth();
        
        // Gather monthly data
        $companiesPerMonth = Company::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        $usersPerMonth = User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        $ticketsPerMonth = Ticket::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Build rows
        $rows = [];
        for ($i = $this->months - 1; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthName = now()->subMonths($i)->locale('es')->isoFormat('MMMM YYYY');
            
            $rows[] = [
                ucfirst($monthName),
                $companiesPerMonth[$monthKey] ?? 0,
                $usersPerMonth[$monthKey] ?? 0,
                $ticketsPerMonth[$monthKey] ?? 0,
            ];
        }
        
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Mes',
            'Nuevas Empresas',
            'Nuevos Usuarios',
            'Nuevos Tickets',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '28A745'], // Green
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Crecimiento Mensual';
    }
}

/**
 * Summary Sheet
 */
class GrowthSummarySheet implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function array(): array
    {
        return [
            ['Total Empresas', Company::count()],
            ['Empresas Activas', Company::where('status', 'active')->count()],
            ['Empresas Suspendidas', Company::where('status', 'suspended')->count()],
            ['Total Usuarios', User::count()],
            ['Total Tickets', Ticket::count()],
        ];
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '17A2B8'], // Info blue
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Resumen General';
    }
}
