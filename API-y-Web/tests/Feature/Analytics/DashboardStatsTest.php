<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function user_can_get_dashboard_stats(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);

        // Create tickets for this user
        Ticket::factory()->count(2)->create([
            'created_by_user_id' => $user->id,
            'company_id' => $company->id,
            'category_id' => $category->id,
            'status' => TicketStatus::OPEN,
        ]);

        Ticket::factory()->count(1)->create([
            'created_by_user_id' => $user->id,
            'company_id' => $company->id,
            'category_id' => $category->id,
            'status' => TicketStatus::RESOLVED,
        ]);

        // Create tickets for another user (should not be counted)
        Ticket::factory()->count(5)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'status' => TicketStatus::OPEN,
        ]);

        // Act
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/analytics/user-dashboard');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'kpi' => [
                'total_tickets',
                'open_tickets',
                'pending_tickets',
                'resolved_tickets',
                'closed_tickets',
            ],
            'ticket_status',
            'recent_tickets',
            'recent_articles',
        ]);

        $response->assertJsonPath('kpi.total_tickets', 3);
        $response->assertJsonPath('kpi.open_tickets', 2);
        $response->assertJsonPath('kpi.resolved_tickets', 1);
    }

    #[Test]
    public function agent_can_get_dashboard_stats(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $agent = User::factory()->withRole('AGENT', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->withRole('USER')->create();

        // Assigned tickets to agent
        Ticket::factory()->count(3)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => $agent->id,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        // Unassigned tickets in company
        Ticket::factory()->count(2)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'owner_agent_id' => null,
            'created_by_user_id' => $user->id,
            'status' => TicketStatus::OPEN,
        ]);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/analytics/agent-dashboard');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'kpi' => [
                'assigned_total',
                'assigned_open',
                'assigned_pending',
                'resolved_today',
            ],
            'ticket_status',
            'assigned_tickets',
            'unassigned_tickets',
            'recent_articles',
        ]);

        $response->assertJsonPath('kpi.assigned_total', 3);
        $response->assertJsonPath('kpi.assigned_open', 3);
        $this->assertCount(2, $response->json('unassigned_tickets'));
    }

    #[Test]
    public function platform_admin_can_get_dashboard_stats(): void
    {
        // Arrange
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();

        // Create some data
        Company::factory()->count(3)->create();
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        
        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/analytics/platform-dashboard');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'kpi' => [
                'total_users',
                'total_companies',
                'total_tickets',
                'pending_requests',
            ],
            'companies_growth',
            'ticket_volume',
            'pending_requests',
            'top_companies',
        ]);

        // Check if counts are at least what we created (there might be seeded data)
        $this->assertGreaterThanOrEqual(3, $response->json('kpi.total_companies'));
        $this->assertGreaterThanOrEqual(2, $response->json('kpi.pending_requests'));
    }

    #[Test]
    public function company_admin_can_get_dashboard_stats(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $admin = User::factory()->withRole('COMPANY_ADMIN', $company->id)->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->withRole('USER')->create();

        // Create tickets in company
        Ticket::factory()->count(4)->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/analytics/company-dashboard');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'kpi',
            'ticket_status',
            'tickets_over_time',
            'recent_tickets',
            'team_members',
            'categories',
            'performance',
        ]);

        $response->assertJsonPath('kpi.total_tickets', 4);
    }

    #[Test]
    public function unauthorized_access_is_forbidden(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Act & Assert
        // User trying to access platform dashboard
        $this->authenticateWithJWT($user)
            ->getJson('/api/analytics/platform-dashboard')
            ->assertForbidden();

        // User trying to access agent dashboard
        $this->authenticateWithJWT($user)
            ->getJson('/api/analytics/agent-dashboard')
            ->assertForbidden();
    }
}
