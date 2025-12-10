<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\Reports\Exports\CompaniesExport;
use App\Features\Reports\Exports\CompanyRequestsExport;
use App\Features\Reports\Exports\PlatformGrowthExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Platform Report Controller
 * 
 * Handles report generation (PDF and Excel) for Platform Admin.
 * Reports available:
 * - Companies Report: List of all companies with stats
 * - Growth Report: Platform growth statistics over time
 * - Company Requests Report: History of company registration requests
 */
class PlatformReportController
{
    // =====================================================================
    // COMPANIES REPORT
    // =====================================================================

    /**
     * Download companies report as Excel
     */
    public function companiesExcel(Request $request)
    {
        $status = $request->get('status');
        $filename = 'empresas_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new CompaniesExport($status), $filename);
    }

    /**
     * Download companies report as PDF
     */
    public function companiesPdf(Request $request)
    {
        $status = $request->get('status');
        
        $query = Company::with(['industry'])
            ->withCount(['tickets'])
            ->withCount(['userRoles as agents_count' => function ($query) {
                $query->where('role_code', 'AGENT')->where('is_active', true);
            }]);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $companies = $query->orderBy('created_at', 'desc')->get();
        
        $pdf = Pdf::loadView('app.platform-admin.reports.templates.companies-pdf', [
            'companies' => $companies,
            'status' => $status,
            'generatedAt' => now(),
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('empresas_' . now()->format('Y-m-d_His') . '.pdf');
    }

    // =====================================================================
    // GROWTH REPORT
    // =====================================================================

    /**
     * Download platform growth report as Excel
     */
    public function growthExcel(Request $request)
    {
        $months = (int) $request->get('months', 6);
        $filename = 'crecimiento_plataforma_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new PlatformGrowthExport($months), $filename);
    }

    /**
     * Download platform growth report as PDF
     */
    public function growthPdf(Request $request)
    {
        $months = (int) $request->get('months', 6);
        
        // Gather growth data
        $data = $this->gatherGrowthData($months);
        
        $pdf = Pdf::loadView('app.platform-admin.reports.templates.growth-pdf', [
            'data' => $data,
            'months' => $months,
            'generatedAt' => now(),
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download('crecimiento_plataforma_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Gather growth statistics data
     */
    private function gatherGrowthData(int $months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        
        // Companies created per month
        $companiesPerMonth = Company::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Users created per month
        $usersPerMonth = User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Tickets created per month
        $ticketsPerMonth = Ticket::where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Fill all months
        $allMonths = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $allMonths[$monthKey] = [
                'month' => $monthKey,
                'month_name' => now()->subMonths($i)->locale('es')->isoFormat('MMMM YYYY'),
                'companies' => $companiesPerMonth[$monthKey] ?? 0,
                'users' => $usersPerMonth[$monthKey] ?? 0,
                'tickets' => $ticketsPerMonth[$monthKey] ?? 0,
            ];
        }
        
        // Summary totals
        $summary = [
            'total_companies' => Company::count(),
            'total_users' => User::count(),
            'total_tickets' => Ticket::count(),
            'active_companies' => Company::where('status', 'active')->count(),
            'pending_requests' => CompanyRequest::where('status', 'pending')->count(),
            'new_companies_period' => array_sum($companiesPerMonth),
            'new_users_period' => array_sum($usersPerMonth),
            'new_tickets_period' => array_sum($ticketsPerMonth),
        ];
        
        return [
            'monthly' => array_values($allMonths),
            'summary' => $summary,
        ];
    }

    // =====================================================================
    // COMPANY REQUESTS REPORT
    // =====================================================================

    /**
     * Download company requests report as Excel
     */
    public function requestsExcel(Request $request)
    {
        $status = $request->get('status');
        $filename = 'solicitudes_empresa_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new CompanyRequestsExport($status), $filename);
    }

    /**
     * Download company requests report as PDF
     */
    public function requestsPdf(Request $request)
    {
        $status = $request->get('status');
        
        $query = CompanyRequest::with(['reviewedBy']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $requests = $query->orderBy('created_at', 'desc')->get();
        
        $pdf = Pdf::loadView('app.platform-admin.reports.templates.requests-pdf', [
            'requests' => $requests,
            'status' => $status,
            'generatedAt' => now(),
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('solicitudes_empresa_' . now()->format('Y-m-d_His') . '.pdf');
    }

    // =====================================================================
    // REPORTS INDEX PAGE
    // =====================================================================

    /**
     * Show reports index page
     */
    public function index()
    {
        return view('app.platform-admin.reports.index');
    }
}
