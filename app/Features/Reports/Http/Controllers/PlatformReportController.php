<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\ExternalIntegration\Models\ServiceApiKey;
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
 * Handles report views and generation (PDF and Excel) for Platform Admin.
 * Reports available:
 * - Companies Report: List of all companies with stats
 * - Growth Report: Platform growth statistics over time
 * - Company Requests Report: History of company registration requests
 * - API Keys Report: Integration API keys usage statistics
 */
class PlatformReportController
{
    // =====================================================================
    // COMPANIES REPORT - VIEW
    // =====================================================================

    /**
     * Show companies report page
     */
    public function companies()
    {
        // Get industries for filter
        $industries = CompanyIndustry::orderBy('name')->get();

        // Get stats
        $stats = [
            'total' => Company::count(),
            'active' => Company::where('status', 'active')->count(),
            'suspended' => Company::where('status', 'suspended')->count(),
            'this_month' => Company::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // Get top 10 companies by tickets
        $topCompanies = Company::with(['industry'])
            ->withCount(['tickets'])
            ->withCount([
                'userRoles as agents_count' => function ($query) {
                    $query->where('role_code', 'AGENT')->where('is_active', true);
                }
            ])
            ->orderBy('tickets_count', 'desc')
            ->limit(10)
            ->get();

        // Chart data - Industries distribution
        $industriesData = Company::select('industry_id', DB::raw('COUNT(*) as total'))
            ->groupBy('industry_id')
            ->with('industry')
            ->get();

        $chartData = [
            'industries' => [
                'labels' => $industriesData->map(fn($i) => $i->industry->name ?? 'Sin industria')->toArray(),
                'values' => $industriesData->pluck('total')->toArray(),
            ],
            'monthly' => $this->getMonthlyCompaniesData(6),
            'status' => [
                'active' => $stats['active'],
                'suspended' => $stats['suspended'],
            ],
        ];

        return view('app.platform-admin.reports.companies', compact(
            'industries',
            'stats',
            'topCompanies',
            'chartData'
        ));
    }

    /**
     * Get monthly companies creation data
     */
    private function getMonthlyCompaniesData(int $months): array
    {
        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->locale('es')->isoFormat('MMM YY');
            $values[] = Company::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // =====================================================================
    // COMPANIES REPORT - DOWNLOADS
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
            ->withCount([
                'userRoles as agents_count' => function ($query) {
                    $query->where('role_code', 'AGENT')->where('is_active', true);
                }
            ]);

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
    // GROWTH REPORT - VIEW
    // =====================================================================

    /**
     * Show growth report page
     */
    public function growth(Request $request)
    {
        $months = (int) $request->get('months', 6);

        // Gather growth data
        $data = $this->gatherGrowthData($months);

        $summary = $data['summary'];
        $monthlyData = $data['monthly'];

        // Chart data for view
        $chartData = [
            'labels' => array_column($monthlyData, 'month_name'),
            'companies' => array_column($monthlyData, 'companies'),
            'users' => array_column($monthlyData, 'users'),
            'tickets' => array_column($monthlyData, 'tickets'),
        ];

        return view('app.platform-admin.reports.growth', compact(
            'summary',
            'monthlyData',
            'chartData',
            'months'
        ));
    }

    // =====================================================================
    // GROWTH REPORT - DOWNLOADS
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
    // COMPANY REQUESTS REPORT - VIEW
    // =====================================================================

    /**
     * Show requests report page
     */
    public function requests()
    {
        // Get industries for filter
        $industries = CompanyIndustry::orderBy('name')->get();

        // Get stats
        $stats = [
            'total' => CompanyRequest::count(),
            'pending' => CompanyRequest::where('status', 'pending')->count(),
            'approved' => CompanyRequest::where('status', 'approved')->count(),
            'rejected' => CompanyRequest::where('status', 'rejected')->count(),
            'this_month' => CompanyRequest::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // Recent requests
        $recentRequests = CompanyRequest::with(['reviewer', 'industry'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Chart data
        $chartData = [
            'monthly' => $this->getMonthlyRequestsData(6),
            'status' => [
                'pending' => $stats['pending'],
                'approved' => $stats['approved'],
                'rejected' => $stats['rejected'],
            ],
        ];

        return view('app.platform-admin.reports.requests', compact(
            'industries',
            'stats',
            'recentRequests',
            'chartData'
        ));
    }

    /**
     * Get monthly requests data by status
     */
    private function getMonthlyRequestsData(int $months): array
    {
        $labels = [];
        $approved = [];
        $pending = [];
        $rejected = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->locale('es')->isoFormat('MMM YY');

            $monthRequests = CompanyRequest::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->get();

            $approved[] = $monthRequests->where('status', 'approved')->count();
            $pending[] = $monthRequests->where('status', 'pending')->count();
            $rejected[] = $monthRequests->where('status', 'rejected')->count();
        }

        return [
            'labels' => $labels,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
        ];
    }

    // =====================================================================
    // COMPANY REQUESTS REPORT - DOWNLOADS
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

        $query = CompanyRequest::with(['reviewer', 'createdCompany', 'industry']);

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
    // API KEYS REPORT - VIEW
    // =====================================================================

    /**
     * Show API Keys report page
     */
    public function apikeys()
    {
        // Get companies for filter
        $companies = Company::orderBy('name')->get();

        // Get stats
        $stats = [
            'total' => ServiceApiKey::count(),
            'active' => ServiceApiKey::where('is_active', true)->notExpired()->count(),
            'inactive' => ServiceApiKey::where('is_active', false)->count(),
            'total_usage' => ServiceApiKey::sum('usage_count'),
            'companies_with_keys' => ServiceApiKey::distinct('company_id')->count('company_id'),
            'usage_24h' => $this->getUsageLast24Hours(),
            'usage_7d' => $this->getUsageLast7Days(),
        ];

        // Top API Keys by usage
        $topApiKeys = ServiceApiKey::with(['company'])
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        // Chart data
        $chartData = [
            'usage' => $this->getDailyUsageData(30),
            'types' => [
                'production' => ServiceApiKey::where('type', 'production')->count(),
                'development' => ServiceApiKey::where('type', 'development')->count(),
                'testing' => ServiceApiKey::where('type', 'testing')->count(),
            ],
        ];

        return view('app.platform-admin.reports.apikeys', compact(
            'companies',
            'stats',
            'topApiKeys',
            'chartData'
        ));
    }

    /**
     * Get daily usage data for chart
     */
    private function getDailyUsageData(int $days): array
    {
        $labels = [];
        $values = [];

        // Since we don't have daily usage logs, we'll simulate with created dates
        // In a real scenario, you'd track API requests in a separate table
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');

            // Count keys used on that day (simplified - based on last_used_at)
            $values[] = ServiceApiKey::whereDate('last_used_at', $date->format('Y-m-d'))->count() * 10;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get usage in last 24 hours (approximation)
     */
    private function getUsageLast24Hours(): int
    {
        $sum = ServiceApiKey::where('last_used_at', '>=', now()->subDay())
            ->sum('usage_count');
        return (int) ($sum ?? 0);
    }

    /**
     * Get usage in last 7 days (approximation)
     */
    private function getUsageLast7Days(): int
    {
        $sum = ServiceApiKey::where('last_used_at', '>=', now()->subWeek())
            ->sum('usage_count');
        return (int) ($sum ?? 0);
    }

    // =====================================================================
    // API KEYS REPORT - DOWNLOADS
    // =====================================================================

    /**
     * Download API Keys report as Excel
     */
    public function apikeysExcel(Request $request)
    {
        $filename = 'api_keys_' . now()->format('Y-m-d_His') . '.xlsx';

        // For now, create a simple CSV-like export
        // You may want to create a proper ApiKeysExport class
        $query = ServiceApiKey::with(['company', 'creator']);

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->get('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->get('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        $apiKeys = $query->orderBy($request->get('sort', 'usage_count'), 'desc')->get();

        // Create simple Excel using Laravel Excel Collection
        return Excel::download(new class ($apiKeys) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $apiKeys;

            public function __construct($apiKeys)
            {
                $this->apiKeys = $apiKeys;
            }

            public function collection()
            {
                return $this->apiKeys->map(fn($key) => [
                    'Nombre' => $key->name,
                    'Empresa' => $key->company->name ?? '-',
                    'Tipo' => $key->type,
                    'Estado' => $key->is_active ? 'Activa' : 'Revocada',
                    'Uso Total' => $key->usage_count,
                    'Último Uso' => $key->last_used_at?->format('d/m/Y H:i') ?? 'Nunca',
                    'Creada' => $key->created_at->format('d/m/Y'),
                ]);
            }

            public function headings(): array
            {
                return ['Nombre', 'Empresa', 'Tipo', 'Estado', 'Uso Total', 'Último Uso', 'Creada'];
            }
        }, $filename);
    }

    /**
     * Download API Keys report as PDF
     */
    public function apikeysPdf(Request $request)
    {
        $query = ServiceApiKey::with(['company', 'creator']);

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->get('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->get('company_id')) {
            $query->where('company_id', $request->get('company_id'));
        }

        $apiKeys = $query->orderBy($request->get('sort', 'usage_count'), 'desc')->get();

        $pdf = Pdf::loadView('app.platform-admin.reports.templates.apikeys-pdf', [
            'apiKeys' => $apiKeys,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('api_keys_' . now()->format('Y-m-d_His') . '.pdf');
    }

    // =====================================================================
    // LEGACY INDEX (Redirect to Companies)
    // =====================================================================

    /**
     * Show reports index page (redirects to companies)
     */
    public function index()
    {
        return redirect()->route('admin.reports.companies');
    }
}
