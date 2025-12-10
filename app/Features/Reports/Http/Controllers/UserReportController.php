<?php

declare(strict_types=1);

namespace App\Features\Reports\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\Reports\Exports\UserTicketsExport;
use App\Shared\Helpers\JWTHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * User Report Controller
 * 
 * Handles report generation (PDF and Excel) for regular Users.
 * Reports available:
 * - Tickets Report: List of all user's tickets with status and priority
 * - Activity Report: User activity summary and statistics
 */
class UserReportController
{
    // =====================================================================
    // TICKETS REPORT
    // =====================================================================

    /**
     * Download user tickets report as Excel
     */
    public function ticketsExcel(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $userId = $user->id;
        $status = $request->get('status');
        
        $filename = 'mis_tickets_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new UserTicketsExport($userId, $status), $filename);
    }

    /**
     * Download user tickets report as PDF
     */
    public function ticketsPdf(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $userId = $user->id;
        $status = $request->get('status');
        
        $query = Ticket::with(['company', 'category'])
            ->where('created_by_user_id', $userId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $tickets = $query->orderBy('created_at', 'desc')->get();
        
        // Get summary stats
        $summary = [
            'total' => Ticket::where('created_by_user_id', $userId)->count(),
            'open' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::OPEN)->count(),
            'pending' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::PENDING)->count(),
            'resolved' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::RESOLVED)->count(),
            'closed' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::CLOSED)->count(),
        ];
        
        $pdf = Pdf::loadView('app.user.reports.templates.tickets-pdf', [
            'tickets' => $tickets,
            'summary' => $summary,
            'userName' => $user->profile?->display_name ?? $user->email,
            'status' => $status,
            'generatedAt' => now(),
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        $filename = 'mis_tickets_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // =====================================================================
    // ACTIVITY REPORT
    // =====================================================================

    /**
     * Download user activity report as Excel
     */
    public function activityExcel(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $userId = $user->id;
        $months = (int) $request->get('months', 6);
        
        $filename = 'mi_actividad_' . now()->format('Y-m-d_His') . '.xlsx';
        
        // For now, we'll use a simple export - could be enhanced later
        return Excel::download(new UserTicketsExport($userId, null), $filename);
    }

    /**
     * Download user activity report as PDF
     */
    public function activityPdf(Request $request)
    {
        $user = JWTHelper::getAuthenticatedUser();
        $userId = $user->id;
        $months = (int) $request->get('months', 6);
        
        // Gather activity data
        $data = $this->gatherActivityData($userId, $months);
        
        $pdf = Pdf::loadView('app.user.reports.templates.activity-pdf', [
            'data' => $data,
            'userName' => $user->profile?->display_name ?? $user->email,
            'months' => $months,
            'generatedAt' => now(),
        ]);
        
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'mi_actividad_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Gather user activity statistics data
     */
    private function gatherActivityData(string $userId, int $months): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        
        // Tickets created per month
        $ticketsPerMonth = Ticket::where('created_by_user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
        
        // Status distribution
        $statusDistribution = Ticket::where('created_by_user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        
        // Priority distribution
        $priorityDistribution = Ticket::where('created_by_user_id', $userId)
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();
        
        // Fill all months
        $allMonths = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $allMonths[$monthKey] = [
                'month' => $monthKey,
                'month_name' => now()->subMonths($i)->locale('es')->isoFormat('MMMM YYYY'),
                'tickets' => $ticketsPerMonth[$monthKey] ?? 0,
            ];
        }
        
        // Summary totals
        $totalTickets = Ticket::where('created_by_user_id', $userId)->count();
        $resolvedTickets = Ticket::where('created_by_user_id', $userId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->count();
        
        $summary = [
            'total_tickets' => $totalTickets,
            'open' => $statusDistribution[TicketStatus::OPEN->value] ?? 0,
            'pending' => $statusDistribution[TicketStatus::PENDING->value] ?? 0,
            'resolved' => $statusDistribution[TicketStatus::RESOLVED->value] ?? 0,
            'closed' => $statusDistribution[TicketStatus::CLOSED->value] ?? 0,
            'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0,
            'tickets_this_period' => array_sum($ticketsPerMonth),
            'priority_high' => $priorityDistribution['HIGH'] ?? 0,
            'priority_medium' => $priorityDistribution['MEDIUM'] ?? 0,
            'priority_low' => $priorityDistribution['LOW'] ?? 0,
        ];
        
        return [
            'monthly' => array_values($allMonths),
            'summary' => $summary,
        ];
    }
}
