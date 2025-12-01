<?php

namespace App\Features\Analytics\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\UserManagement\Models\User;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\TicketManagement\Models\Category;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
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
     * Get dashboard statistics for a specific user (Customer/Employee).
     *
     * @param string $userId
     * @return array
     */
    public function getUserDashboardStats(string $userId): array
    {
        return [
            'kpi' => $this->getUserKpiStats($userId),
            'ticket_status' => $this->getUserTicketStatusStats($userId),
            'recent_tickets' => $this->getUserRecentTickets($userId),
            'recent_articles' => $this->getRecentArticles(),
        ];
    }

    /**
     * Get dashboard statistics for an Agent.
     *
     * @param string $agentId
     * @param string $companyId
     * @return array
     */
    public function getAgentDashboardStats(string $agentId, string $companyId): array
    {
        return [
            'kpi' => $this->getAgentKpiStats($agentId),
            'ticket_status' => $this->getAgentTicketStatusStats($agentId),
            'assigned_tickets' => $this->getAgentAssignedTickets($agentId),
            'unassigned_tickets' => $this->getCompanyUnassignedTickets($companyId),
            'recent_articles' => $this->getRecentArticles(),
        ];
    }

    /**
     * Get dashboard statistics for Platform Admin.
     *
     * @return array
     */
    public function getPlatformDashboardStats(): array
    {
        return [
            'kpi' => $this->getPlatformKpiStats(),
            'companies_growth' => $this->getCompaniesGrowth(),
            'ticket_volume' => $this->getGlobalTicketVolume(),
            'pending_requests' => $this->getPendingCompanyRequests(),
            'top_companies' => $this->getTopCompaniesByTicketVolume(),
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
     * Get Platform KPI counts.
     */
    private function getPlatformKpiStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_companies' => Company::count(),
            'total_tickets' => Ticket::count(),
            'pending_requests' => CompanyRequest::where('status', 'pending')->count(),
        ];
    }

    /**
     * Get Companies Growth (Last 6 months).
     */
    private function getCompaniesGrowth(): array
    {
        $result = Company::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'labels' => array_keys($result),
            'data' => array_values($result),
        ];
    }

    /**
     * Get Global Ticket Volume (Last 6 months).
     */
    private function getGlobalTicketVolume(): array
    {
        $result = Ticket::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'labels' => array_keys($result),
            'data' => array_values($result),
        ];
    }

    /**
     * Get Pending Company Requests.
     */
    private function getPendingCompanyRequests(int $limit = 5): array
    {
        return CompanyRequest::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'company_name' => $request->company_name,
                    'admin_email' => $request->admin_email,
                    'created_at' => $request->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get Top Companies by Ticket Volume.
     */
    private function getTopCompaniesByTicketVolume(int $limit = 5): array
    {
        return Company::withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($company) {
                return [
                    'name' => $company->name,
                    'tickets_count' => $company->tickets_count,
                    'status' => $company->status,
                ];
            })
            ->toArray();
    }

    /**
     * Get Agent KPI counts.
     */
    private function getAgentKpiStats(string $agentId): array
    {
        return [
            'assigned_total' => Ticket::where('owner_agent_id', $agentId)->count(),
            'assigned_open' => Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::OPEN)->count(),
            'assigned_pending' => Ticket::where('owner_agent_id', $agentId)->where('status', TicketStatus::PENDING)->count(),
            'resolved_today' => Ticket::where('owner_agent_id', $agentId)
                ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
                ->whereDate('updated_at', now()->today())
                ->count(),
        ];
    }

    /**
     * Get Agent ticket counts by status.
     */
    private function getAgentTicketStatusStats(string $agentId): array
    {
        $stats = Ticket::where('owner_agent_id', $agentId)
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
     * Get tickets assigned to the agent.
     */
    private function getAgentAssignedTickets(string $agentId, int $limit = 5): array
    {
        return Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->with(['category', 'creator'])
            ->orderBy('priority', 'desc') // High priority first
            ->orderBy('created_at', 'asc') // Oldest first
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticket_code' => $ticket->ticket_code,
                    'title' => $ticket->title,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'creator_name' => $ticket->creator->name ?? 'Unknown',
                    'created_at' => $ticket->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get unassigned tickets for the company (Queue).
     */
    private function getCompanyUnassignedTickets(string $companyId, int $limit = 5): array
    {
        return Ticket::where('company_id', $companyId)
            ->whereNull('owner_agent_id')
            ->where('status', TicketStatus::OPEN)
            ->with(['category', 'creator'])
            ->orderBy('created_at', 'asc') // Oldest first (FIFO)
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticket_code' => $ticket->ticket_code,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority->value,
                    'category' => $ticket->category->name ?? 'Uncategorized',
                    'created_at' => $ticket->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get User KPI counts.
     */
    private function getUserKpiStats(string $userId): array
    {
        return [
            'total_tickets' => Ticket::where('created_by_user_id', $userId)->count(),
            'open_tickets' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::OPEN)->count(),
            'pending_tickets' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::PENDING)->count(),
            'resolved_tickets' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::RESOLVED)->count(),
            'closed_tickets' => Ticket::where('created_by_user_id', $userId)->where('status', TicketStatus::CLOSED)->count(),
        ];
    }

    /**
     * Get User ticket counts by status.
     */
    private function getUserTicketStatusStats(string $userId): array
    {
        $stats = Ticket::where('created_by_user_id', $userId)
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
     * Get User recent tickets.
     */
    private function getUserRecentTickets(string $userId, int $limit = 5): array
    {
        return Ticket::where('created_by_user_id', $userId)
            ->with(['category']) // Load category for display
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticket_code' => $ticket->ticket_code,
                    'title' => $ticket->title,
                    'status' => $ticket->status->value,
                    'priority' => $ticket->priority->value,
                    'category' => $ticket->category->name ?? 'Uncategorized',
                    'created_at' => $ticket->created_at->toIso8601String(),
                    'updated_at' => $ticket->updated_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent help center articles (global for now).
     */
    private function getRecentArticles(int $limit = 5): array
    {
        return HelpCenterArticle::published()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->id, // Use ID as slug for now since slug is missing
                    'views' => $article->views_count,
                ];
            })
            ->toArray();
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
