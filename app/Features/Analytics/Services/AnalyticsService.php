<?php

namespace App\Features\Analytics\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\UserManagement\Models\User;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get comprehensive dashboard statistics for a company.
     *
     * @param string $companyId
     * @return array
     */
    public function getCompanyDashboardStats(string $companyId): array
    {
        return [
            'kpi' => $this->getKpiStats($companyId),
            'ticket_status' => $this->getTicketStatusStats($companyId),
            'tickets_over_time' => $this->getTicketsOverTime($companyId),
            'recent_tickets' => $this->getRecentTickets($companyId),
            'team_members' => $this->getTeamStats($companyId),
            'categories' => $this->getCategoryStats($companyId),
            'performance' => $this->getPerformanceStats($companyId),
        ];
    }

    /**
     * Get KPI counts.
     */
    private function getKpiStats(string $companyId): array
    {
        return [
            'total_agents' => User::active()
                ->whereHas('userRoles', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->where('role_code', 'AGENT');
                })
                ->count(),
            'total_articles' => HelpCenterArticle::where('company_id', $companyId)->count(),
            'total_announcements' => Announcement::where('company_id', $companyId)->count(),
            'total_tickets' => Ticket::where('company_id', $companyId)->count(),
        ];
    }

    /**
     * Get ticket counts by status.
     */
    private function getTicketStatusStats(string $companyId): array
    {
        $stats = Ticket::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'OPEN' => $stats[TicketStatus::OPEN->value] ?? 0,
            'PENDING' => $stats[TicketStatus::PENDING->value] ?? 0,
            'RESOLVED' => $stats[TicketStatus::RESOLVED->value] ?? 0,
            'CLOSED' => $stats[TicketStatus::CLOSED->value] ?? 0,
        ];
    }

    /**
     * Get tickets created over the last 6 months.
     */
    private function getTicketsOverTime(string $companyId): array
    {
        $data = Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill missing months
        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $result[$month] = $data[$month] ?? 0;
        }

        return [
            'labels' => array_keys($result),
            'data' => array_values($result),
        ];
    }

    /**
     * Get recent tickets.
     */
    private function getRecentTickets(string $companyId, int $limit = 5): array
    {
        return Ticket::where('company_id', $companyId)
            ->with(['creator.profile'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticket_code' => $ticket->ticket_code,
                    'title' => $ticket->title,
                    'status' => $ticket->status->value,
                    'created_at' => $ticket->created_at->toIso8601String(),
                    'creator_name' => $ticket->creator->displayName ?? 'Unknown',
                ];
            })
            ->toArray();
    }

    /**
     * Get team members (agents).
     */
    private function getTeamStats(string $companyId, int $limit = 5): array
    {
        return User::active()
            ->whereHas('userRoles', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->where('role_code', 'AGENT');
            })
            ->with('profile')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                // Determine online status (active in last 5 minutes)
                $isOnline = $user->last_activity_at && $user->last_activity_at->diffInMinutes(now()) < 5;
                
                return [
                    'name' => $user->displayName,
                    'email' => $user->email,
                    'avatar_url' => $user->avatarUrl,
                    'status' => $isOnline ? 'ONLINE' : 'OFFLINE',
                ];
            })
            ->toArray();
    }

    /**
     * Get categories with active ticket counts.
     */
    private function getCategoryStats(string $companyId): array
    {
        $totalActiveTickets = Ticket::where('company_id', $companyId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->count();

        return Category::where('company_id', $companyId)
            ->withCount(['tickets as active_tickets_count' => function ($query) {
                $query->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING]);
            }])
            ->get()
            ->map(function ($category) use ($totalActiveTickets) {
                $percentage = $totalActiveTickets > 0 
                    ? round(($category->active_tickets_count / $totalActiveTickets) * 100) 
                    : 0;
                
                return [
                    'name' => $category->name,
                    'active_tickets_count' => $category->active_tickets_count,
                    'percentage' => $percentage,
                ];
            })
            ->toArray();
    }

    /**
     * Get performance metrics.
     */
    private function getPerformanceStats(string $companyId): array
    {
        // Calculate average resolution time for resolved/closed tickets
        $avgResolutionTime = Ticket::where('company_id', $companyId)
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))) as avg_seconds'))
            ->value('avg_seconds');

        // Format to hours/minutes
        $avgTimeFormatted = '--';
        if ($avgResolutionTime) {
            $hours = floor($avgResolutionTime / 3600);
            $minutes = floor(($avgResolutionTime % 3600) / 60);
            $avgTimeFormatted = "{$hours}h {$minutes}m";
        }

        return [
            'avg_response_time' => $avgTimeFormatted,
        ];
    }
}
