<?php

namespace App\Features\Analytics\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Models\Area;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        try {
            Log::info('Getting company dashboard stats for company: ' . $companyId);
            
            $kpi = $this->getKpiStats($companyId);
            Log::info('KPI stats retrieved');
            
            $ticketStatus = $this->getTicketStatusStats($companyId);
            Log::info('Ticket status stats retrieved');
            
            $ticketPriority = $this->getTicketPriorityStats($companyId);
            Log::info('Ticket priority stats retrieved');
            
            $ticketsOverTime = $this->getTicketsOverTime($companyId);
            Log::info('Tickets over time retrieved');
            
            $topAgents = $this->getTopAgentsByPerformance($companyId);
            Log::info('Top agents retrieved: ' . count($topAgents));
            
            $teamMembers = $this->getTeamStats($companyId);
            Log::info('Team members retrieved');
            
            $categories = $this->getCategoryStats($companyId);
            Log::info('Categories retrieved');
            
            $performance = $this->getPerformanceStats($companyId);
            Log::info('Performance stats retrieved');
            
            return [
                'kpi' => $kpi,
                'ticket_status' => $ticketStatus,
                'ticket_priority' => $ticketPriority,
                'tickets_over_time' => $ticketsOverTime,
                'top_agents' => $topAgents,
                'team_members' => $teamMembers,
                'categories' => $categories,
                'performance' => $performance,
            ];
        } catch (\Exception $e) {
            Log::error('Error in getCompanyDashboardStats: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
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
            // Datos adicionales para cumplir con requisitos de dashboard estadístico
            'profile' => $this->getUserProfile($userId),
            'priority_distribution' => $this->getUserPriorityDistribution($userId),
            'tickets_trend' => $this->getUserTicketsTrend($userId),
            'top_companies' => $this->getTopFollowedCompanies(),
            'top_articles' => $this->getTopViewedArticles(),
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
            // New enhanced metrics for agent dashboard
            'weekly_activity' => $this->getAgentWeeklyActivity($agentId),
            'priority_distribution' => $this->getAgentPriorityDistribution($agentId),
            'performance_metrics' => $this->getAgentPerformanceMetrics($agentId),
            'agent_profile' => $this->getAgentProfile($agentId),
            'recent_activity' => $this->getAgentRecentActivity($agentId),
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
            'company_requests_stats' => $this->getCompanyRequestsStats(),
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
     * Get Company Requests Stats (breakdown by status).
     */
    private function getCompanyRequestsStats(): array
    {
        $stats = CompanyRequest::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'labels' => ['Pendientes', 'Aprobadas', 'Rechazadas'],
            'data' => [
                $stats['pending'] ?? 0,
                $stats['approved'] ?? 0,
                $stats['rejected'] ?? 0,
            ],
            'backgroundColor' => ['#FFC107', '#28A745', '#DC3545'],
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
     * Get ticket counts by priority.
     */
    private function getTicketPriorityStats(string $companyId): array
    {
        $stats = Ticket::where('company_id', $companyId)
            ->whereNotNull('priority')
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        return [
            'labels' => ['Baja', 'Media', 'Alta'],
            'data' => [
                $stats['low'] ?? 0,
                $stats['medium'] ?? 0,
                $stats['high'] ?? 0,
            ],
            'colors' => ['#28a745', '#ffc107', '#dc3545'], // green, yellow, red
        ];
    }

    /**
     * Get top agents by performance.
     */
    private function getTopAgentsByPerformance(string $companyId, int $limit = 5): array
    {
        $agents = User::with('profile')
            ->whereHas('userRoles', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->where('role_code', 'AGENT');
            })
            ->get();

        // Calculate stats for each agent
        $agentStats = $agents->map(function ($agent) use ($companyId) {
            $assignedCount = Ticket::where('company_id', $companyId)
                ->where('owner_agent_id', $agent->id)
                ->count();

            $resolvedCount = Ticket::where('company_id', $companyId)
                ->where('owner_agent_id', $agent->id)
                ->whereIn('status', ['resolved', 'closed'])
                ->count();

            $resolutionRate = $assignedCount > 0 
                ? round(($resolvedCount / $assignedCount) * 100) 
                : 0;

            $fullName = $agent->profile 
                ? trim($agent->profile->first_name . ' ' . $agent->profile->last_name)
                : $agent->email;

            return [
                'agent' => $agent,
                'name' => $fullName,
                'email' => $agent->email,
                'assigned' => $assignedCount,
                'resolved' => $resolvedCount,
                'resolution_rate' => $resolutionRate,
            ];
        })
        ->filter(fn($stats) => $stats['assigned'] > 0)
        ->sortByDesc('resolved')
        ->sortByDesc('assigned')
        ->take($limit)
        ->values();

        return $agentStats->map(function ($stats, $index) {
            return [
                'rank' => $index + 1,
                'name' => $stats['name'],
                'email' => $stats['email'],
                'assigned' => $stats['assigned'],
                'resolved' => $stats['resolved'],
                'resolution_rate' => $stats['resolution_rate'],
            ];
        })->toArray();
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

    /**
     * Get comprehensive statistics for a specific company.
     * Optimized for Platform Admin view - all stats in one call.
     * Uses efficient grouped queries to avoid N+1 problems.
     *
     * @param string $companyId
     * @return array
     */
    public function getCompanyFullStats(string $companyId): array
    {
        return [
            'users' => $this->getCompanyUserStats($companyId),
            'tickets' => $this->getCompanyTicketStats($companyId),
            'announcements' => $this->getCompanyAnnouncementStats($companyId),
            'articles' => $this->getCompanyArticleStats($companyId),
            'areas' => $this->getCompanyAreaStats($companyId),
            'categories' => $this->getCompanyCategorySummary($companyId),
        ];
    }

    /**
     * Get user statistics for a company.
     */
    private function getCompanyUserStats(string $companyId): array
    {
        // Get role counts in a single query
        $roleCounts = UserRole::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('role_code', DB::raw('COUNT(DISTINCT user_id) as total'))
            ->groupBy('role_code')
            ->pluck('total', 'role_code')
            ->toArray();

        // Get followers count
        $followersCount = DB::table('business.user_company_followers')
            ->where('company_id', $companyId)
            ->count();

        return [
            'total' => array_sum($roleCounts),
            'admins' => $roleCounts['COMPANY_ADMIN'] ?? 0,
            'agents' => $roleCounts['AGENT'] ?? 0,
            'followers' => $followersCount,
        ];
    }

    /**
     * Get ticket statistics for a company.
     * Single optimized query with grouping.
     */
    private function getCompanyTicketStats(string $companyId): array
    {
        // Status counts in one query
        $statusCounts = Ticket::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Priority counts in one query
        $priorityCounts = Ticket::where('company_id', $companyId)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $total = array_sum($statusCounts);

        return [
            'total' => $total,
            'by_status' => [
                'open' => $statusCounts[TicketStatus::OPEN->value] ?? 0,
                'pending' => $statusCounts[TicketStatus::PENDING->value] ?? 0,
                'resolved' => $statusCounts[TicketStatus::RESOLVED->value] ?? 0,
                'closed' => $statusCounts[TicketStatus::CLOSED->value] ?? 0,
            ],
            'by_priority' => [
                'low' => $priorityCounts[TicketPriority::LOW->value] ?? 0,
                'medium' => $priorityCounts[TicketPriority::MEDIUM->value] ?? 0,
                'high' => $priorityCounts[TicketPriority::HIGH->value] ?? 0,
            ],
        ];
    }

    /**
     * Get announcement statistics for a company.
     */
    private function getCompanyAnnouncementStats(string $companyId): array
    {
        // Type counts
        $typeCounts = Announcement::where('company_id', $companyId)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        // Status counts
        $statusCounts = Announcement::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total = array_sum($typeCounts);

        return [
            'total' => $total,
            'by_type' => [
                'MAINTENANCE' => $typeCounts[AnnouncementType::MAINTENANCE->value] ?? 0,
                'INCIDENT' => $typeCounts[AnnouncementType::INCIDENT->value] ?? 0,
                'NEWS' => $typeCounts[AnnouncementType::NEWS->value] ?? 0,
                'ALERT' => $typeCounts[AnnouncementType::ALERT->value] ?? 0,
            ],
            'by_status' => [
                'DRAFT' => $statusCounts[PublicationStatus::DRAFT->value] ?? 0,
                'SCHEDULED' => $statusCounts[PublicationStatus::SCHEDULED->value] ?? 0,
                'PUBLISHED' => $statusCounts[PublicationStatus::PUBLISHED->value] ?? 0,
                'ARCHIVED' => $statusCounts[PublicationStatus::ARCHIVED->value] ?? 0,
            ],
        ];
    }

    /**
     * Get help center article statistics for a company.
     */
    private function getCompanyArticleStats(string $companyId): array
    {
        // Status counts and total views in one query
        $stats = HelpCenterArticle::where('company_id', $companyId)
            ->select(
                'status',
                DB::raw('count(*) as total'),
                DB::raw('sum(views_count) as views')
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $total = $stats->sum('total');
        $totalViews = $stats->sum('views');

        return [
            'total' => $total,
            'by_status' => [
                'DRAFT' => $stats->get('DRAFT')?->total ?? 0,
                'PUBLISHED' => $stats->get('PUBLISHED')?->total ?? 0,
                'ARCHIVED' => $stats->get('ARCHIVED')?->total ?? 0,
            ],
            'total_views' => (int) $totalViews,
        ];
    }

    /**
     * Get area statistics for a company.
     */
    private function getCompanyAreaStats(string $companyId): array
    {
        // Check if company has areas enabled
        $company = Company::find($companyId);
        $areasEnabled = $company?->hasAreasEnabled() ?? false;

        // Area counts by active status
        $areaCounts = Area::where('company_id', $companyId)
            ->select('is_active', DB::raw('count(*) as total'))
            ->groupBy('is_active')
            ->pluck('total', 'is_active')
            ->toArray();

        return [
            'total' => array_sum($areaCounts),
            'active' => $areaCounts[true] ?? $areaCounts[1] ?? 0,
            'inactive' => $areaCounts[false] ?? $areaCounts[0] ?? 0,
            'areas_enabled' => $areasEnabled,
        ];
    }

    /**
     * Get category summary for a company.
     */
    private function getCompanyCategorySummary(string $companyId): array
    {
        // Category counts
        $categoryCounts = Category::where('company_id', $companyId)
            ->select('is_active', DB::raw('count(*) as total'))
            ->groupBy('is_active')
            ->pluck('total', 'is_active')
            ->toArray();

        return [
            'total' => array_sum($categoryCounts),
            'active' => $categoryCounts[true] ?? $categoryCounts[1] ?? 0,
            'inactive' => $categoryCounts[false] ?? $categoryCounts[0] ?? 0,
        ];
    }

    // =========================================================================
    // ENHANCED AGENT DASHBOARD METHODS
    // =========================================================================

    /**
     * Get agent's weekly activity (tickets resolved per day for last 7 days).
     */
    private function getAgentWeeklyActivity(string $agentId): array
    {
        $data = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->where('updated_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw("TO_CHAR(updated_at, 'YYYY-MM-DD') as day"),
                DB::raw('count(*) as total')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day')
            ->toArray();

        // Fill missing days with 0
        $result = [];
        $dayNames = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayKey = $date->format('Y-m-d');
            $result[$dayKey] = $data[$dayKey] ?? 0;
            $dayNames[] = $date->locale('es')->isoFormat('ddd'); // Mon, Tue, etc
        }

        return [
            'labels' => $dayNames,
            'data' => array_values($result),
        ];
    }

    /**
     * Get agent's ticket distribution by priority.
     */
    private function getAgentPriorityDistribution(string $agentId): array
    {
        $stats = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $total = array_sum($stats);

        return [
            'high' => [
                'count' => $stats[TicketPriority::HIGH->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::HIGH->value] ?? 0) / $total) * 100) : 0,
            ],
            'medium' => [
                'count' => $stats[TicketPriority::MEDIUM->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::MEDIUM->value] ?? 0) / $total) * 100) : 0,
            ],
            'low' => [
                'count' => $stats[TicketPriority::LOW->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::LOW->value] ?? 0) / $total) * 100) : 0,
            ],
            'total' => $total,
        ];
    }

    /**
     * Get agent's performance metrics (workload, open rate, pending rate, resolution rate).
     */
    private function getAgentPerformanceMetrics(string $agentId): array
    {
        // Get counts for all agent's tickets
        $statusCounts = Ticket::where('owner_agent_id', $agentId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total = array_sum($statusCounts);
        $open = $statusCounts[TicketStatus::OPEN->value] ?? 0;
        $pending = $statusCounts[TicketStatus::PENDING->value] ?? 0;
        $resolved = $statusCounts[TicketStatus::RESOLVED->value] ?? 0;
        $closed = $statusCounts[TicketStatus::CLOSED->value] ?? 0;

        // Active tickets = open + pending
        $activeTickets = $open + $pending;
        
        // Resolved today
        $resolvedToday = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->whereDate('updated_at', now()->today())
            ->count();

        // Capacity assumption: 20 tickets is 100% workload
        $workloadCapacity = 20;
        $workloadPercentage = min(100, round(($activeTickets / $workloadCapacity) * 100));

        // Open rate: percentage of active tickets that are open
        $openRate = $activeTickets > 0 ? round(($open / $activeTickets) * 100) : 0;
        
        // Pending rate: percentage of active tickets that are pending
        $pendingRate = $activeTickets > 0 ? round(($pending / $activeTickets) * 100) : 0;

        // Resolution rate: resolved+closed vs total
        $resolutionRate = $total > 0 ? round((($resolved + $closed) / $total) * 100) : 0;

        return [
            'workload' => $workloadPercentage,
            'open_rate' => $openRate,
            'pending_rate' => $pendingRate,
            'resolution_rate' => $resolutionRate,
            'active_tickets' => $activeTickets,
            'resolved_today' => $resolvedToday,
        ];
    }

    /**
     * Get agent's profile information.
     */
    private function getAgentProfile(string $agentId): array
    {
        $user = User::with('profile')->find($agentId);
        
        if (!$user) {
            return [
                'name' => 'Unknown',
                'role' => 'Agent',
                'avatar_url' => null,
                'member_since' => null,
                'total_resolved' => 0,
                'total_assigned' => 0,
            ];
        }

        // Get total resolved tickets (all time)
        $totalResolved = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->count();

        // Get total assigned tickets (currently active)
        $totalAssigned = Ticket::where('owner_agent_id', $agentId)
            ->whereIn('status', [TicketStatus::OPEN, TicketStatus::PENDING])
            ->count();

        return [
            'name' => $user->displayName ?? $user->name,
            'role' => 'Agente de Soporte',
            'avatar_url' => $user->avatarUrl,
            'member_since' => $user->created_at?->translatedFormat('M Y'),
            'total_resolved' => $totalResolved,
            'total_assigned' => $totalAssigned,
        ];
    }

    /**
     * Get agent's recent activity (timeline events).
     */
    private function getAgentRecentActivity(string $agentId, int $limit = 5): array
    {
        // Get recent tickets that were updated by this agent
        $recentTickets = Ticket::where('owner_agent_id', $agentId)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        $activities = [];
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        foreach ($recentTickets as $ticket) {
            $isResolved = in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED]);
            
            // Determine the activity type based on status
            if ($isResolved) {
                $type = 'resolved';
                $icon = 'fas fa-check-circle';
                $color = 'bg-success';
                $message = "Ticket #{$ticket->ticket_code} resuelto";
            } elseif ($ticket->status === TicketStatus::PENDING) {
                $type = 'pending';
                $icon = 'fas fa-clock';
                $color = 'bg-warning';
                $message = "Ticket #{$ticket->ticket_code} en espera";
            } else {
                $type = 'assigned';
                $icon = 'fas fa-ticket-alt';
                $color = 'bg-info';
                $message = "Ticket #{$ticket->ticket_code} asignado";
            }

            // Group by date
            $updatedAt = $ticket->updated_at;
            if ($updatedAt >= $today) {
                $dateGroup = 'Hoy';
            } elseif ($updatedAt >= $yesterday) {
                $dateGroup = 'Ayer';
            } else {
                $dateGroup = $updatedAt->translatedFormat('d M Y');
            }

            $activities[] = [
                'type' => $type,
                'icon' => $icon,
                'color' => $color,
                'message' => $message,
                'description' => $ticket->title,
                'time' => $updatedAt->format('h:i A'),
                'date_group' => $dateGroup,
                'ticket_code' => $ticket->ticket_code,
            ];
        }

        return $activities;
    }

    // =========================================================================
    // USER DASHBOARD ENHANCEMENT METHODS
    // =========================================================================

    /**
     * Get user's profile information for dashboard.
     */
    private function getUserProfile(string $userId): array
    {
        $user = User::with('profile')->find($userId);
        
        if (!$user) {
            return [
                'name' => 'Usuario',
                'avatar_url' => null,
                'member_since' => null,
                'email' => null,
            ];
        }

        // Get resolution rate
        $totalTickets = Ticket::where('created_by_user_id', $userId)->count();
        $resolvedTickets = Ticket::where('created_by_user_id', $userId)
            ->whereIn('status', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->count();
        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100) : 0;

        return [
            'name' => $user->profile?->display_name ?? $user->email,
            'avatar_url' => $user->profile?->avatar_url,
            'member_since' => $user->created_at?->translatedFormat('M Y'),
            'email' => $user->email,
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'resolution_rate' => $resolutionRate,
        ];
    }

    /**
     * Get user's ticket distribution by priority.
     */
    private function getUserPriorityDistribution(string $userId): array
    {
        $stats = Ticket::where('created_by_user_id', $userId)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->toArray();

        $total = array_sum($stats);

        return [
            'high' => [
                'count' => $stats[TicketPriority::HIGH->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::HIGH->value] ?? 0) / $total) * 100) : 0,
            ],
            'medium' => [
                'count' => $stats[TicketPriority::MEDIUM->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::MEDIUM->value] ?? 0) / $total) * 100) : 0,
            ],
            'low' => [
                'count' => $stats[TicketPriority::LOW->value] ?? 0,
                'percentage' => $total > 0 ? round((($stats[TicketPriority::LOW->value] ?? 0) / $total) * 100) : 0,
            ],
            'total' => $total,
        ];
    }

    /**
     * Get user's ticket creation trend over last 6 months.
     */
    private function getUserTicketsTrend(string $userId): array
    {
        $data = Ticket::where('created_by_user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill missing months with 0
        $result = [];
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            $result[$month] = $data[$month] ?? 0;
            $labels[] = $date->translatedFormat('M');
        }

        return [
            'labels' => $labels,
            'data' => array_values($result),
        ];
    }

    /**
     * Get top 5 most followed companies.
     */
    private function getTopFollowedCompanies(int $limit = 5): array
    {
        return Company::active()
            ->withCount('followers')
            ->orderBy('followers_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_url' => $company->logo_url,
                    'followers_count' => $company->followers_count,
                    'industry' => $company->industry?->name ?? 'General',
                ];
            })
            ->toArray();
    }

    /**
     * Get top 5 most viewed articles.
     */
    private function getTopViewedArticles(int $limit = 5): array
    {
        return HelpCenterArticle::published()
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'views_count' => $article->views_count,
                    'category' => $article->category?->name ?? 'General',
                    'published_at' => $article->published_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }
}

