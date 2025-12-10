<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use Illuminate\Routing\Controller;
use App\Shared\Helpers\JWTHelper;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Platform Admin Dashboard Controller
 *
 * Handles dashboard view for PLATFORM_ADMIN role.
 * Shows system-wide statistics and management tools.
 *
 * Uses HYBRID approach:
 * - Static metrics: Passed from controller (SSR - immediate render)
 * - Real-time data: Loaded via AJAX from frontend
 */
class PlatformAdminController extends Controller
{
    /**
     * Display the platform admin dashboard.
     *
     * Passes pre-computed chart data for immediate rendering.
     * Real-time data still loaded via AJAX.
     *
     * @return View
     */
    public function dashboard(): View
    {
        // Get growth data for charts (Server-Side Rendered)
        $growthData = $this->getGrowthChartData();
        $industryData = $this->getIndustryDistribution();
        
        return view('app.platform-admin.dashboard', [
            'growthData' => $growthData,
            'industryData' => $industryData,
        ]);
    }

    /**
     * Get growth chart data (Empresas + Usuarios + Tickets por mes)
     * Last 6 months
     */
    private function getGrowthChartData(): array
    {
        $months = 6;
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        // Empresas por mes
        $companiesPerMonth = Company::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Usuarios por mes
        $usersPerMonth = User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Tickets por mes
        $ticketsPerMonth = Ticket::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Build labels and data arrays
        $labels = [];
        $companiesData = [];
        $usersData = [];
        $ticketsData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthLabel = $date->locale('es')->isoFormat('MMM YYYY'); // "Dic 2024"
            
            $labels[] = ucfirst($monthLabel);
            $companiesData[] = $companiesPerMonth[$monthKey] ?? 0;
            $usersData[] = $usersPerMonth[$monthKey] ?? 0;
            $ticketsData[] = $ticketsPerMonth[$monthKey] ?? 0;
        }

        return [
            'labels' => $labels,
            'companies' => $companiesData,
            'users' => $usersData,
            'tickets' => $ticketsData,
        ];
    }

    /**
     * Get industry distribution for pie/donut chart
     */
    private function getIndustryDistribution(): array
    {
        $industries = Company::select('industry_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('industry_id')
            ->groupBy('industry_id')
            ->with('industry:id,name')
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            '#007bff', // Blue
            '#28a745', // Green
            '#ffc107', // Yellow
            '#dc3545', // Red
            '#6f42c1', // Purple
            '#17a2b8', // Cyan
            '#fd7e14', // Orange
            '#20c997', // Teal
            '#e83e8c', // Pink
            '#6c757d', // Gray
        ];

        foreach ($industries as $index => $item) {
            $labels[] = $item->industry?->name ?? 'Sin industria';
            $data[] = $item->total;
        }

        // Add "Sin industria" count
        $noIndustryCount = Company::whereNull('industry_id')->count();
        if ($noIndustryCount > 0) {
            $labels[] = 'Sin clasificar';
            $data[] = $noIndustryCount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_slice($colors, 0, count($labels)),
        ];
    }
}
