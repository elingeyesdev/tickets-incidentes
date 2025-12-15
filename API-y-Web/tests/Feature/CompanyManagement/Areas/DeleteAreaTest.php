<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Areas;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Deleting Areas
 *
 * Tests the endpoint DELETE /api/areas/{id}
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
 * - Authorization (only same company COMPANY_ADMIN or PLATFORM_ADMIN)
 * - Cannot delete area with active tickets (OPEN, PENDING, RESOLVED)
 * - Can delete area with only CLOSED tickets
 * - Can delete area with no tickets
 *
 * Expected Status Codes:
 * - 200: Area deleted successfully
 * - 400: Cannot delete area with active tickets
 * - 401: Unauthenticated
 * - 403: Insufficient permissions or wrong company
 * - 404: Area not found
 *
 * Database Schema: business.areas
 * Business Rule: Cannot delete areas with tickets in status OPEN, PENDING, or RESOLVED
 */
class DeleteAreaTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Authentication & Authorization (Tests 1-7) ====================

    /**
     * Test #1: Unauthenticated user cannot delete area
     *
     * Expected: 401 Unauthorized
     */
    #[Test]
    public function unauthenticated_user_cannot_delete_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id]);

        // Act - No authenticateWithJWT() call
        $response = $this->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #2: USER cannot delete area
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function user_cannot_delete_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->withRole('USER')->create();

        // Act
        $response = $this->authenticateWithJWT($user)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #3: AGENT cannot delete area
     *
     * Expected: 403 Forbidden (middleware blocks)
     */
    #[Test]
    public function agent_cannot_delete_area(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $area = Area::factory()->create(['company_id' => $company->id]);
        $agent = User::factory()->create();
        $agent->assignRole('AGENT', $company->id);

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #4: COMPANY_ADMIN can delete area of their company
     *
     * Expected: 200 OK
     */
    #[Test]
    public function company_admin_can_delete_area_of_their_company(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Area deleted successfully');

        $this->assertDatabaseMissing('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #5: COMPANY_ADMIN cannot delete area of another company
     *
     * Expected: 403 Forbidden
     */
    #[Test]
    public function company_admin_cannot_delete_area_of_another_company(): void
    {
        // Arrange
        $adminCompanyA = $this->createCompanyAdmin();
        $companyB = Company::factory()->create();

        $area = Area::factory()->create(['company_id' => $companyB->id]);

        // Act
        $response = $this->authenticateWithJWT($adminCompanyA)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'You do not have permission to delete this area');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #6: PLATFORM_ADMIN can delete area (with COMPANY_ADMIN role)
     *
     * Expected: 200 OK
     */
    #[Test]
    public function platform_admin_can_delete_area_of_any_company(): void
    {
        // Arrange
        $platformAdmin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $company = Company::factory()->create();

        // Assign COMPANY_ADMIN role to PLATFORM_ADMIN for this company
        $platformAdmin->assignRole('COMPANY_ADMIN', $company->id);

        $area = Area::factory()->create(['company_id' => $company->id]);

        // Act
        $response = $this->authenticateWithJWT($platformAdmin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #7: Area not found returns 404
     *
     * Expected: 404 Not Found
     */
    #[Test]
    public function area_not_found_returns_404(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $fakeId = Str::uuid()->toString();

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$fakeId}");

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Area not found');
    }

    // ==================== GROUP 2: Active Tickets Constraints (Tests 8-11) ====================

    /**
     * Test #8: Cannot delete area with OPEN tickets
     *
     * Expected: 400 Bad Request
     * Database: Area should still exist
     */
    #[Test]
    public function cannot_delete_area_with_open_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);
        $category = Category::factory()->create(['company_id' => $companyId]);
        $user = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'open',
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Cannot delete area with active tickets');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #9: Cannot delete area with PENDING tickets
     *
     * Expected: 400 Bad Request
     */
    #[Test]
    public function cannot_delete_area_with_pending_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);
        $category = Category::factory()->create(['company_id' => $companyId]);
        $user = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'pending',
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Cannot delete area with active tickets');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #10: Cannot delete area with RESOLVED tickets
     *
     * Expected: 400 Bad Request
     */
    #[Test]
    public function cannot_delete_area_with_resolved_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);
        $category = Category::factory()->create(['company_id' => $companyId]);
        $user = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'resolved',
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Cannot delete area with active tickets');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    /**
     * Test #11: Can delete area with only CLOSED tickets
     *
     * Expected: 200 OK
     * Database: Area should be deleted
     */
    #[Test]
    public function can_delete_area_with_only_closed_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);
        $category = Category::factory()->create(['company_id' => $companyId]);
        $user = User::factory()->withRole('USER')->create();

        Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'closed',
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Area deleted successfully');

        $this->assertDatabaseMissing('business.areas', [
            'id' => $area->id,
        ]);
    }

    // ==================== GROUP 3: Mixed Tickets Scenarios (Test 12) ====================

    /**
     * Test #12: Cannot delete area with mixed tickets (CLOSED + OPEN)
     *
     * Expected: 400 Bad Request (even with 1 active ticket)
     */
    #[Test]
    public function cannot_delete_area_with_mixed_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);
        $category = Category::factory()->create(['company_id' => $companyId]);
        $user = User::factory()->withRole('USER')->create();

        // 3 closed tickets
        Ticket::factory()->count(3)->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'closed',
            'created_by_user_id' => $user->id,
        ]);

        // 1 open ticket (blocks deletion)
        Ticket::factory()->create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'area_id' => $area->id,
            'status' => 'open',
            'created_by_user_id' => $user->id,
        ]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Cannot delete area with active tickets');

        $this->assertDatabaseHas('business.areas', [
            'id' => $area->id,
        ]);
    }

    // ==================== GROUP 4: No Tickets Scenarios (Test 13) ====================

    /**
     * Test #13: Can delete area with no tickets
     *
     * Expected: 200 OK
     */
    #[Test]
    public function can_delete_area_with_no_tickets(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $companyId = $admin->userRoles()->where('role_code', 'COMPANY_ADMIN')->first()->company_id;

        $area = Area::factory()->create(['company_id' => $companyId]);

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->deleteJson("/api/areas/{$area->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Area deleted successfully');

        $this->assertDatabaseMissing('business.areas', [
            'id' => $area->id,
        ]);
    }
}
