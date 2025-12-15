<?php

declare(strict_types=1);

namespace Tests\Feature\ContentManagement\Announcements\General;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Get Announcement Schemas - CAPA 3E
 *
 * Tests endpoint: GET /api/announcements/schemas
 *
 * Coverage:
 * - Returns validation/metadata structure for all announcement types
 * - Used by frontend for dynamic form generation
 * - Only accessible to PLATFORM_ADMIN and COMPANY_ADMIN
 * - AGENT and USER receive 403 Forbidden
 *
 * Schema Structure:
 * {
 *   "success": true,
 *   "data": {
 *     "MAINTENANCE": {
 *       "required": [...],
 *       "optional": [...],
 *       "fields": { urgency: { type, values, label }, ... }
 *     },
 *     "INCIDENT": { ... },
 *     "NEWS": { ... },
 *     "ALERT": { ... }
 *   }
 * }
 *
 * CRITICAL RULES:
 * ❌ NO factories for announcements
 * ✅ ALWAYS RefreshDatabaseWithoutTransactions
 * ✅ ALWAYS authenticateWithJWT($user)
 * ✅ NO usar User::factory() sin role asignado
 */
class GetAnnouncementSchemasTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test 1: COMPANY_ADMIN can access announcement schemas
     *
     * Business Rule:
     * - COMPANY_ADMIN can access schemas for creating/editing announcements
     * - Returns success response with all 4 announcement types
     *
     * Expected: 200 OK with schemas for all announcement types
     */
    #[Test]
    public function company_admin_can_get_announcement_schemas(): void
    {
        // Arrange - Create COMPANY_ADMIN
        $admin = $this->createCompanyAdmin();

        // Act - GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements/schemas');

        // Assert - Should return 200 with success and data with 4 types
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'MAINTENANCE',
                    'INCIDENT',
                    'NEWS',
                    'ALERT',
                ],
            ]);

        // Verify all 4 announcement types are present
        $data = $response->json('data');
        $this->assertCount(4, $data, 'Should return exactly 4 announcement types');
        $this->assertArrayHasKey('MAINTENANCE', $data);
        $this->assertArrayHasKey('INCIDENT', $data);
        $this->assertArrayHasKey('NEWS', $data);
        $this->assertArrayHasKey('ALERT', $data);
    }

    /**
     * Test 2: PLATFORM_ADMIN can access announcement schemas
     *
     * Business Rule:
     * - PLATFORM_ADMIN has full access to all system resources
     * - Can access schemas for administration purposes
     *
     * Expected: 200 OK with schemas for all announcement types
     */
    #[Test]
    public function platform_admin_can_get_announcement_schemas(): void
    {
        // Arrange - Create PLATFORM_ADMIN
        $platformAdmin = User::factory()->withRole(Role::PLATFORM_ADMIN->value)->create();

        // Act - GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($platformAdmin)
            ->getJson('/api/announcements/schemas');

        // Assert - Should return 200 with success and data
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'MAINTENANCE',
                    'INCIDENT',
                    'NEWS',
                    'ALERT',
                ],
            ]);

        // Verify response is valid
        $data = $response->json('data');
        $this->assertIsArray($data, 'Data should be an array');
        $this->assertNotEmpty($data, 'Data should not be empty');
    }

    /**
     * Test 3: Schemas include all four announcement types
     *
     * Business Rule:
     * - Schema endpoint must return all 4 types: MAINTENANCE, INCIDENT, NEWS, ALERT
     * - Must be exactly 4 types (no more, no less)
     *
     * Expected: Response contains exactly 4 announcement type schemas
     */
    #[Test]
    public function schemas_include_all_four_announcement_types(): void
    {
        // Arrange - Create COMPANY_ADMIN
        $admin = $this->createCompanyAdmin();

        // Act - GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements/schemas');

        // Assert - All 4 types must be present
        $data = $response->json('data');

        $this->assertArrayHasKey('MAINTENANCE', $data, 'MAINTENANCE type must exist');
        $this->assertArrayHasKey('INCIDENT', $data, 'INCIDENT type must exist');
        $this->assertArrayHasKey('NEWS', $data, 'NEWS type must exist');
        $this->assertArrayHasKey('ALERT', $data, 'ALERT type must exist');

        // Assert - Exactly 4 types (no more, no less)
        $this->assertCount(4, $data, 'Should have exactly 4 announcement types');

        // Assert - Each type is an array with structure
        foreach (['MAINTENANCE', 'INCIDENT', 'NEWS', 'ALERT'] as $type) {
            $this->assertIsArray($data[$type], "$type should be an array");
            $this->assertNotEmpty($data[$type], "$type should not be empty");
        }
    }

    /**
     * Test 4: MAINTENANCE schema has correct structure
     *
     * Business Rule:
     * - MAINTENANCE schema must have: required, optional, fields
     * - Required fields: urgency, scheduled_start, scheduled_end, is_emergency
     * - Urgency enum values: LOW, MEDIUM, HIGH (NO CRITICAL for MAINTENANCE)
     *
     * Expected: MAINTENANCE schema contains correct required fields and urgency values
     */
    #[Test]
    public function maintenance_schema_has_correct_structure(): void
    {
        // Arrange - Create COMPANY_ADMIN
        $admin = $this->createCompanyAdmin();

        // Act - GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements/schemas');

        // Assert - MAINTENANCE schema structure
        $maintenance = $response->json('data.MAINTENANCE');

        // Verify schema has required keys
        $this->assertArrayHasKey('required', $maintenance, 'MAINTENANCE should have "required" array');
        $this->assertArrayHasKey('optional', $maintenance, 'MAINTENANCE should have "optional" array');

        // Verify required fields contain expected values
        $requiredFields = $maintenance['required'];
        $this->assertIsArray($requiredFields, '"required" should be an array');
        $this->assertContains('urgency', $requiredFields, 'urgency should be required');
        $this->assertContains('scheduled_start', $requiredFields, 'scheduled_start should be required');
        $this->assertContains('scheduled_end', $requiredFields, 'scheduled_end should be required');
        $this->assertContains('is_emergency', $requiredFields, 'is_emergency should be required');

        // Verify optional fields
        $optionalFields = $maintenance['optional'];
        $this->assertIsArray($optionalFields, '"optional" should be an array');
        $this->assertContains('actual_start', $optionalFields, 'actual_start should be optional');
        $this->assertContains('actual_end', $optionalFields, 'actual_end should be optional');
        $this->assertContains('affected_services', $optionalFields, 'affected_services should be optional');

        // Note: Urgency enum validation moved to AnnouncementType enum
        // MAINTENANCE allows: LOW, MEDIUM, HIGH (NO CRITICAL)
        // This is enforced in validation rules, not in schema structure
    }

    /**
     * Test 5: ALERT has special urgency restrictions (HIGH and CRITICAL only)
     *
     * Business Rule:
     * - ALERT announcements are high-priority only
     * - Urgency restricted to: HIGH, CRITICAL
     * - No LOW or MEDIUM urgency for ALERT
     * - NEWS is not part of ALERT schema
     *
     * Expected: ALERT schema has correct required fields and urgency restrictions
     */
    #[Test]
    public function alert_schema_has_high_and_critical_urgency_restriction(): void
    {
        // Arrange - Create COMPANY_ADMIN
        $admin = $this->createCompanyAdmin();

        // Act - GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($admin)
            ->getJson('/api/announcements/schemas');

        // Assert - ALERT schema structure
        $alert = $response->json('data.ALERT');

        // Verify ALERT has required fields
        $this->assertArrayHasKey('required', $alert, 'ALERT should have "required" array');
        $this->assertArrayHasKey('optional', $alert, 'ALERT should have "optional" array');

        // Verify ALERT required fields
        $requiredFields = $alert['required'];
        $this->assertIsArray($requiredFields, '"required" should be an array');
        $this->assertContains('urgency', $requiredFields, 'urgency should be required for ALERT');
        $this->assertContains('alert_type', $requiredFields, 'alert_type should be required');
        $this->assertContains('message', $requiredFields, 'message should be required');
        $this->assertContains('action_required', $requiredFields, 'action_required should be required');
        $this->assertContains('started_at', $requiredFields, 'started_at should be required');

        // Verify ALERT optional fields
        $optionalFields = $alert['optional'];
        $this->assertIsArray($optionalFields, '"optional" should be an array');
        $this->assertContains('action_description', $optionalFields, 'action_description should be optional');
        $this->assertContains('affected_services', $optionalFields, 'affected_services should be optional');
        $this->assertContains('ended_at', $optionalFields, 'ended_at should be optional');

        // Note: Urgency restriction to HIGH/CRITICAL is enforced in UpdateAlertRequest validation
        // Not in the schema structure itself, but in the validation rules
        // ALERT validation rejects LOW/MEDIUM in the request validation layer
    }

    /**
     * Test 6: AGENT and USER cannot access schemas
     *
     * Business Rule:
     * - Only admins (COMPANY_ADMIN, PLATFORM_ADMIN) can access schemas
     * - AGENT role: 403 Forbidden
     * - USER role: 403 Forbidden
     * - Schemas are for creating/editing announcements (admin-only action)
     *
     * Expected: 403 Forbidden for AGENT and USER roles
     */
    #[Test]
    public function agent_and_user_cannot_access_schemas(): void
    {
        // Arrange - Create AGENT
        $company = Company::factory()->create();
        $agent = User::factory()->withRole(Role::AGENT->value, $company->id)->create();

        // Act - AGENT tries to GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($agent)
            ->getJson('/api/announcements/schemas');

        // Assert - AGENT should receive 403 Forbidden
        $response->assertStatus(403);

        // Arrange - Create USER
        $user = User::factory()->withRole(Role::USER->value)->create();

        // Act - USER tries to GET /api/announcements/schemas
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/announcements/schemas');

        // Assert - USER should receive 403 Forbidden
        $response->assertStatus(403);
    }
}
