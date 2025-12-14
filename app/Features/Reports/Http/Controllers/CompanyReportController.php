<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\TicketManagement\Models\Category as TicketCategory;
use App\Features\CompanyManagement\Models\Area;
use App\Features\Reports\Exports\CompanyTicketsExport;
use App\Features\Reports\Exports\AgentsPerformanceExport;
use App\Shared\Helpers\JWTHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Company Report Controller
 * 
 * Handles report generation (PDF and Excel) for Company Admin.
 * Reports available:
 * - Tickets Report: All company tickets with filters
 * - Agents Report: Agent performance metrics
 * - Summary Report: Operational overview
 * - Company Report: Company info + team
 */
class CompanyReportController
{
    // =====================================================================
    // TICKETS REPORT
    // =====================================================================

    public function ticketsExcel(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $status = $request->get('status');
        $priority = $request->get('priority');
        $agentId = $request->get('agent_id');

        $filename = 'tickets_empresa_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new CompanyTicketsExport($companyId, $status, $priority, $agentId), $filename);
    }

    public function ticketsPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::find($companyId);
        $status = $request->get('status');
        $priority = $request->get('priority');
        $agentId = $request->get('agent_id');

        $query = Ticket::with(['creator.profile', 'ownerAgent.profile', 'category'])
            ->where('company_id', $companyId)
            ->withCount(['responses', 'attachments']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($agentId) {
            $query->where('owner_agent_id', $agentId);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        // Summary stats
        $summary = [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', TicketStatus::OPEN)->count(),
            'pending' => $tickets->where('status', TicketStatus::PENDING)->count(),
            'resolved' => $tickets->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => $tickets->where('status', TicketStatus::CLOSED)->count(),
        ];

        $pdf = Pdf::loadView('app.company-admin.reports.templates.tickets-pdf', [
            'tickets' => $tickets,
            'summary' => $summary,
            'company' => $company,
            'filters' => ['status' => $status, 'priority' => $priority, 'agent_id' => $agentId],
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'tickets_empresa_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // AGENTS REPORT
    // =====================================================================

    public function agentsExcel(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();

        $filename = 'rendimiento_agentes_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new AgentsPerformanceExport($companyId), $filename);
    }

    public function agentsPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::find($companyId);

        // Get all agents for this company
        $agents = User::whereHas('userRoles', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('role_code', 'AGENT');
        })->with('profile')->get();

        $agentStats = [];
        foreach ($agents as $agent) {
            $total = Ticket::where('owner_agent_id', $agent->id)->count();
            $active = Ticket::where('owner_agent_id', $agent->id)
                ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])->count();
            $resolved = Ticket::where('owner_agent_id', $agent->id)
                ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])->count();
            $today = Ticket::where('owner_agent_id', $agent->id)
                ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
                ->whereDate('updated_at', now()->today())->count();

            $agentStats[] = [
                'name' => $agent->profile?->display_name ?? $agent->email,
                'email' => $agent->email,
                'total' => $total,
                'active' => $active,
                'resolved' => $resolved,
                'today' => $today,
                'rate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
                'since' => $agent->created_at?->format('d/m/Y'),
            ];
        }

        // Sort by resolved (best performers first)
        usort($agentStats, fn($a, $b) => $b['resolved'] <=> $a['resolved']);

        // Build summary
        $totalTickets = array_sum(array_column($agentStats, 'total'));
        $avgTickets = count($agentStats) > 0 ? round($totalTickets / count($agentStats)) : 0;
        $bestAgent = !empty($agentStats) ? $agentStats[0]['name'] : '-';

        $summary = [
            'total_agents' => count($agentStats),
            'avg_tickets_per_agent' => $avgTickets,
            'best_agent' => $bestAgent,
        ];

        // Map agentStats to expected format
        $agentsForView = array_map(function ($a) {
            return [
                'name' => $a['name'],
                'email' => $a['email'],
                'assigned_tickets' => $a['total'],
                'active_tickets' => $a['active'],
                'resolved_tickets' => $a['resolved'],
                'resolved_today' => $a['today'],
                'resolution_rate' => $a['rate'],
                'member_since' => $a['since'],
            ];
        }, $agentStats);

        $pdf = Pdf::loadView('app.company-admin.reports.templates.agents-pdf', [
            'agents' => $agentsForView,
            'company' => $company,
            'summary' => $summary,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'rendimiento_agentes_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // SUMMARY REPORT
    // =====================================================================

    public function summaryPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::find($companyId);
        $months = (int) $request->get('months', 6);

        // KPIs
        $kpis = [
            'total_tickets' => Ticket::where('company_id', $companyId)->count(),
            'open' => Ticket::where('company_id', $companyId)->where('status', TicketStatus::OPEN)->count(),
            'pending' => Ticket::where('company_id', $companyId)->where('status', TicketStatus::PENDING)->count(),
            'resolved' => Ticket::where('company_id', $companyId)->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => Ticket::where('company_id', $companyId)->where('status', TicketStatus::CLOSED)->count(),
            'total_agents' => User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId)->where('role_code', 'AGENT'))->count(),
            'total_articles' => HelpCenterArticle::where('company_id', $companyId)->count(),
            'total_announcements' => Announcement::where('company_id', $companyId)->count(),
        ];

        // Priority distribution
        $priorityStats = Ticket::where('company_id', $companyId)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        // Top categories
        $topCategories = Ticket::where('company_id', $companyId)
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->with('category')
            ->get()
            ->map(fn($t) => ['name' => $t->category?->name ?? 'Sin categoría', 'count' => $t->total]);

        // Configuration
        $config = [
            'total_categories' => TicketCategory::where('company_id', $companyId)->count(),
            'total_areas' => Area::where('company_id', $companyId)->count(),
            'active_areas' => Area::where('company_id', $companyId)->where('is_active', true)->count(),
        ];

        // Monthly trend
        $startDate = now()->subMonths($months - 1)->startOfMonth();
        $monthlyCreated = Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyResolved = Ticket::where('company_id', $companyId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->where('updated_at', '>=', $startDate)
            ->select(DB::raw("TO_CHAR(updated_at, 'YYYY-MM') as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyData = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthlyData[] = [
                'month' => now()->subMonths($i)->locale('es')->isoFormat('MMM YYYY'),
                'created' => $monthlyCreated[$monthKey] ?? 0,
                'resolved' => $monthlyResolved[$monthKey] ?? 0,
            ];
        }

        // Format distributions for view
        $distributions = [
            'by_status' => [
                'open' => $kpis['open'],
                'pending' => $kpis['pending'],
                'resolved' => $kpis['resolved'],
                'closed' => $kpis['closed'],
            ],
            'by_priority' => [
                'high' => $priorityStats[TicketPriority::HIGH->value] ?? 0,
                'medium' => $priorityStats[TicketPriority::MEDIUM->value] ?? 0,
                'low' => $priorityStats[TicketPriority::LOW->value] ?? 0,
            ],
            'top_categories' => $topCategories->map(function ($cat, $index) use ($kpis) {
                return [
                    'name' => $cat['name'],
                    'count' => $cat['count'],
                    'percentage' => $kpis['total_tickets'] > 0 ? round(($cat['count'] / $kpis['total_tickets']) * 100, 1) : 0,
                ];
            })->toArray(),
        ];

        $pdf = Pdf::loadView('app.company-admin.reports.templates.summary-pdf', [
            'company' => $company,
            'kpis' => $kpis,
            'distributions' => $distributions,
            'config' => $config,
            'monthlyData' => $monthlyData,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'resumen_operativo_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // COMPANY & TEAM REPORT
    // =====================================================================

    public function companyPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::with(['industry', 'admin.profile'])->find($companyId);

        // Get all team members
        $agents = User::whereHas('userRoles', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('role_code', 'AGENT');
        })->with('profile')->get()->map(function ($agent) {
            $ticketCount = Ticket::where('owner_agent_id', $agent->id)->count();
            return [
                'name' => $agent->profile?->display_name ?? $agent->email,
                'email' => $agent->email,
                'tickets' => $ticketCount,
                'since' => $agent->created_at?->format('d/m/Y'),
            ];
        });

        // Get company admins
        $admins = User::whereHas('userRoles', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('role_code', 'COMPANY_ADMIN');
        })->with('profile')->get()->map(function ($admin) {
            return [
                'name' => $admin->profile?->display_name ?? $admin->email,
                'email' => $admin->email,
                'created_at' => $admin->created_at,
            ];
        });

        // Format agents for view
        $agentsForView = $agents->map(function ($agent) {
            return [
                'name' => $agent['name'],
                'email' => $agent['email'],
                'assigned_tickets' => $agent['tickets'],
                'member_since' => $agent['since'],
            ];
        });

        $pdf = Pdf::loadView('app.company-admin.reports.templates.company-pdf', [
            'company' => $company,
            'admins' => $admins,
            'agents' => $agentsForView,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'empresa_y_equipo_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // ANNOUNCEMENTS REPORT
    // =====================================================================

    public function announcementsPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::find($companyId);
        $status = $request->get('status');
        $type = $request->get('type');

        $query = Announcement::with(['author.profile'])
            ->where('company_id', $companyId);

        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $announcements = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('app.company-admin.reports.templates.announcements-pdf', [
            'announcements' => $announcements,
            'company' => $company,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'anuncios_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // ARTICLES REPORT
    // =====================================================================

    public function articlesPdf(Request $request)
    {
        $companyId = JWTHelper::getActiveCompanyId();
        $company = Company::find($companyId);
        $status = $request->get('status');
        $categoryId = $request->get('category_id');

        $query = HelpCenterArticle::with(['author.profile', 'category'])
            ->where('company_id', $companyId);

        if ($status) {
            $query->where('status', $status);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $articles = $query->orderBy('views_count', 'desc')->get();

        $pdf = Pdf::loadView('app.company-admin.reports.templates.articles-pdf', [
            'articles' => $articles,
            'company' => $company,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'articulos_centro_ayuda_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}
