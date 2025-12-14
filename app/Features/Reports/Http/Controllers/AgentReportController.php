<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\Reports\Exports\AgentTicketsExport;
use App\Shared\Helpers\JWTHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Agent Report Controller
 * 
 * Handles report generation for AGENT role.
 * - Tickets Report: Agent's assigned tickets
 * - Performance Report: Agent's personal stats
 */
class AgentReportController
{
    // =====================================================================
    // TICKETS REPORT
    // =====================================================================

    public function ticketsExcel(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user['id'];
        
        $status = $request->get('status');
        $priority = $request->get('priority');
        $categoryId = $request->get('category_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $filename = 'mis_tickets_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new AgentTicketsExport($agentId, $status, $priority, $categoryId, $dateFrom, $dateTo),
            $filename
        );
    }

    public function ticketsPdf(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user['id'];
        $agentName = $user['profile']['display_name'] ?? $user['email'] ?? 'Agente';

        // Get filters
        $status = $request->get('status');
        $priority = $request->get('priority');
        $categoryId = $request->get('category_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Ticket::with(['creator.profile', 'category'])
            ->where('owner_agent_id', $agentId)
            ->withCount(['responses', 'attachments']);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        // Summary (based on filtered results)
        $summary = [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', TicketStatus::OPEN)->count(),
            'pending' => $tickets->where('status', TicketStatus::PENDING)->count(),
            'resolved' => $tickets->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => $tickets->where('status', TicketStatus::CLOSED)->count(),
        ];

        // Build filter description
        $filterText = collect([
            $status ? "Estado: {$status}" : null,
            $priority ? "Prioridad: {$priority}" : null,
            $dateFrom ? "Desde: {$dateFrom}" : null,
            $dateTo ? "Hasta: {$dateTo}" : null,
        ])->filter()->implode(' | ');

        $pdf = Pdf::loadView('app.agent.reports.templates.tickets-pdf', [
            'tickets' => $tickets,
            'summary' => $summary,
            'agentName' => $agentName,
            'filter' => $filterText ?: null,
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'mis_tickets_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // PERFORMANCE REPORT
    // =====================================================================

    public function performancePdf(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user['id'];
        $agentName = $user['profile']['display_name'] ?? $user['email'] ?? 'Agente';

        // Stats
        $total = Ticket::where('owner_agent_id', $agentId)->count();
        $open = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::OPEN)->count();
        $pending = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::PENDING)->count();
        $resolved = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::RESOLVED)->count();
        $closed = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::CLOSED)->count();
        $resolvedToday = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->whereDate('updated_at', now()->today())->count();

        $rate = $total > 0 ? round((($resolved + $closed) / $total) * 100) : 0;

        // Priority distribution
        $high = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::HIGH)->count();
        $medium = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::MEDIUM)->count();
        $low = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::LOW)->count();

        $stats = [
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolved,
            'closed' => $closed,
            'resolved_today' => $resolvedToday,
            'resolution_rate' => $rate,
            'priority' => ['high' => $high, 'medium' => $medium, 'low' => $low],
        ];

        $pdf = Pdf::loadView('app.agent.reports.templates.performance-pdf', [
            'agentName' => $agentName,
            'stats' => $stats,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'mi_rendimiento_' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // =====================================================================
    // VIEW METHODS (Server-side data for Blade views)
    // =====================================================================

    /**
     * Tickets Report View
     */
    public function tickets(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user->id;
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        // Tickets
        $tickets = Ticket::with(['creator.profile', 'category'])
            ->where('owner_agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // KPIs
        $total = Ticket::where('owner_agent_id', $agentId)->count();
        $open = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::OPEN)->count();
        $pending = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::PENDING)->count();
        $resolved = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::RESOLVED)->count();
        $closed = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::CLOSED)->count();

        $kpis = [
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolved + $closed,
        ];

        $statusStats = [
            'OPEN' => $open,
            'PENDING' => $pending,
            'RESOLVED' => $resolved,
            'CLOSED' => $closed,
        ];

        // Priority Stats
        $high = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::HIGH)->count();
        $medium = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::MEDIUM)->count();
        $low = Ticket::where('owner_agent_id', $agentId)->where('priority', TicketPriority::LOW)->count();

        $priorityStats = [
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
        ];

        // Monthly Data (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Ticket::where('owner_agent_id', $agentId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $monthlyData[] = [
                'label' => $date->locale('es')->isoFormat('MMM'),
                'count' => $count,
            ];
        }

        // Top Categories
        $topCategories = Ticket::where('owner_agent_id', $agentId)
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('category')
            ->get()
            ->map(fn($t) => [
                'name' => $t->category?->name ?? 'Sin categoría',
                'count' => $t->total,
            ])->toArray();

        // Categories for filter
        $categories = \App\Features\TicketManagement\Models\Category::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('app.agent.reports.tickets', [
            'user' => $user,
            'companyId' => $companyId,
            'tickets' => $tickets,
            'kpis' => $kpis,
            'statusStats' => $statusStats,
            'priorityStats' => $priorityStats,
            'monthlyData' => $monthlyData,
            'topCategories' => $topCategories,
            'categories' => $categories,
        ]);
    }

    /**
     * Performance Report View
     */
    public function performance(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user->id;
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        // Status Stats
        $total = Ticket::where('owner_agent_id', $agentId)->count();
        $open = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::OPEN)->count();
        $pending = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::PENDING)->count();
        $resolved = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::RESOLVED)->count();
        $closed = Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::CLOSED)->count();
        $resolvedToday = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->whereDate('updated_at', now()->today())->count();

        // Priority (active tickets only)
        $high = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->where('priority', TicketPriority::HIGH)->count();
        $medium = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->where('priority', TicketPriority::MEDIUM)->count();
        $low = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->where('priority', TicketPriority::LOW)->count();

        // Weekly Activity (last 7 days)
        $weeklyLabels = [];
        $weeklyValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayCount = Ticket::where('owner_agent_id', $agentId)
                ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
                ->whereDate('updated_at', $date->toDateString())
                ->count();
            $weeklyLabels[] = $date->locale('es')->isoFormat('ddd');
            $weeklyValues[] = $dayCount;
        }

        $resolvedTotal = $resolved + $closed;
        $rate = $total > 0 ? round(($resolvedTotal / $total) * 100) : 0;

        $kpis = [
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolvedTotal,
            'resolved_today' => $resolvedToday,
        ];

        $metrics = [
            'resolution_rate' => $rate,
            'open_rate' => $total > 0 ? round(($open / $total) * 100) : 0,
            'pending_rate' => $total > 0 ? round(($pending / $total) * 100) : 0,
        ];

        $priority = [
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
        ];

        $chartData = [
            'labels' => $weeklyLabels,
            'data' => $weeklyValues,
        ];

        $statusStats = [
            'OPEN' => $open,
            'PENDING' => $pending,
            'RESOLVED' => $resolved,
            'CLOSED' => $closed,
        ];

        return view('app.agent.reports.performance', [
            'user' => $user,
            'companyId' => $companyId,
            'kpis' => $kpis,
            'metrics' => $metrics,
            'priority' => $priority,
            'chartData' => $chartData,
            'statusStats' => $statusStats,
        ]);
    }
}
