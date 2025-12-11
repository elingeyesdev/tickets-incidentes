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
        
        $filename = 'mis_tickets_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new AgentTicketsExport($agentId, $status), $filename);
    }

    public function ticketsPdf(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $agentId = $user['id'];
        $agentName = $user['profile']['display_name'] ?? $user['email'] ?? 'Agente';
        $status = $request->get('status');
        
        $query = Ticket::with(['creator.profile', 'category'])
            ->where('owner_agent_id', $agentId)
            ->withCount(['responses', 'attachments']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $tickets = $query->orderBy('created_at', 'desc')->get();
        
        // Summary
        $summary = [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', TicketStatus::OPEN)->count(),
            'pending' => $tickets->where('status', TicketStatus::PENDING)->count(),
            'resolved' => $tickets->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => $tickets->where('status', TicketStatus::CLOSED)->count(),
        ];
        
        $pdf = Pdf::loadView('app.agent.reports.templates.tickets-pdf', [
            'tickets' => $tickets,
            'summary' => $summary,
            'agentName' => $agentName,
            'filter' => $status,
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
}
