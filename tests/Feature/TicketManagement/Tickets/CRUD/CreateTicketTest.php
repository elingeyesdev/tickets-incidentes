<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement\Tickets\CRUD;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Feature Tests for Creating Tickets
 *
 * Tests the endpoint POST /api/v1/tickets
 *
 * Coverage:
 * - Authentication (unauthenticated, USER, AGENT, COMPANY_ADMIN)
 * - Required fields validation (title, initial_description, company_id, category_id)
 * - Title validation (length 5-255)
 * - Description validation (length 10-5000)
 * - Company existence validation
 * - Category existence and active status validation
 * - Permissions by role (only USER can create tickets)
 * - Ticket code generation (automatic, sequential per year)
 * - Initial status (open)
 * - created_by_user_id assignment
 * - Event triggering (TicketCreated)
 *
 * Expected Status Codes:
 * - 201: Ticket created successfully
 * - 401: Unauthenticated
 * - 403: Insufficient permissions (AGENT, COMPANY_ADMIN)
 * - 422: Validation errors
 *
 * Database Schema: ticketing.tickets
 * - id: UUID (auto-generated)
 * - ticket_code: VARCHAR(50) (auto-generated: TKT-YYYY-XXXXX)
 * - company_id: UUID (FK to business.companies)
 * - category_id: UUID (FK to ticketing.categories)
 * - created_by_user_id: UUID (FK to auth.users)
 * - owner_agent_id: UUID (nullable, FK to auth.users)
 * - title: VARCHAR(255) NOT NULL
 * - initial_description: TEXT NOT NULL
 * - status: ENUM (open, pending, resolved, closed) DEFAULT 'open'
 * - created_at: TIMESTAMPTZ
 * - updated_at: TIMESTAMPTZ
 */
class CreateTicketTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    // ==================== GROUP 1: Permisos y Autenticación (Tests 1-4) ====================

    /**
     * Test #1: User can create ticket
     *
     * Verifies that users with USER role can successfully create tickets.
     *
     * Expected: 201 Created with ticket data
     * Database: Ticket should be persisted
     * Response: Should include id, ticket_code, company_id, category_id, title, initial_description, status, created_by_user_id
     */
    #[Test]
    public function user_can_create_ticket(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Error al exportar reporte mensual',
            'initial_description' => 'Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Error al exportar reporte mensual');
        $response->assertJsonPath('data.company_id', $company->id);
        $response->assertJsonPath('data.category_id', $category->id);
        $response->assertJsonPath('data.created_by_user_id', $user->id);
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonStructure([
            'data' => [
                'id',
                'ticket_code',
                'company_id',
                'category_id',
                'title',
                'initial_description',
                'status',
                'created_by_user_id',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Error al exportar reporte mensual',
            'company_id' => $company->id,
            'category_id' => $category->id,
            'created_by_user_id' => $user->id,
            'status' => 'open',
        ]);
    }

    /**
     * Test #2: Agent cannot create ticket
     *
     * Verifies that users with AGENT role are forbidden from creating tickets.
     * Only USER role should be able to create tickets.
     *
     * Expected: 403 Forbidden
     * Database: No ticket should be created
     */
    #[Test]
    public function agent_cannot_create_ticket(): void
    {
        // Arrange
        $agent = User::factory()->withRole('AGENT')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket creado por agente',
            'initial_description' => 'Esto no debería permitirse porque el agente no puede crear tickets.',
        ];

        // Act
        $response = $this->authenticateWithJWT($agent)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.tickets', [
            'title' => 'Ticket creado por agente',
        ]);
    }

    /**
     * Test #3: Company admin cannot create ticket
     *
     * Verifies that users with COMPANY_ADMIN role are forbidden from creating tickets.
     * Only USER role should be able to create tickets.
     *
     * Expected: 403 Forbidden
     * Database: No ticket should be created
     */
    #[Test]
    public function company_admin_cannot_create_ticket(): void
    {
        // Arrange
        $admin = $this->createCompanyAdmin();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket creado por admin',
            'initial_description' => 'Esto no debería permitirse porque el admin no puede crear tickets.',
        ];

        // Act
        $response = $this->authenticateWithJWT($admin)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(403);

        $this->assertDatabaseMissing('ticketing.tickets', [
            'title' => 'Ticket creado por admin',
        ]);
    }

    /**
     * Test #4: Unauthenticated user cannot create ticket
     *
     * Verifies that requests without JWT authentication are rejected.
     *
     * Expected: 401 Unauthorized
     * Database: No ticket should be created
     */
    #[Test]
    public function unauthenticated_user_cannot_create_ticket(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket sin autenticación',
            'initial_description' => 'Esto no debería permitirse porque no hay usuario autenticado.',
        ];

        // Act - No authenticateWithJWT() call
        $response = $this->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(401);

        $this->assertDatabaseMissing('ticketing.tickets', [
            'title' => 'Ticket sin autenticación',
        ]);
    }

    // ==================== GROUP 2: Validación Required (Test 5) ====================

    /**
     * Test #5: Validates required fields
     *
     * Verifies that title, initial_description, company_id, and category_id are required.
     *
     * Expected: 422 Unprocessable Entity with validation errors
     * Database: No ticket should be created
     */
    #[Test]
    public function validates_required_fields(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();

        // Empty payload (missing all required fields)
        $payload = [];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'initial_description', 'company_id', 'category_id']);
    }

    // ==================== GROUP 3: Validación Length (Tests 6-7) ====================

    /**
     * Test #6: Validates title length (min 5, max 255)
     *
     * Verifies title validation constraints:
     * - Minimum 5 characters (should fail)
     * - Maximum 255 characters (should fail)
     *
     * Expected: 422 for invalid lengths
     */
    #[Test]
    public function validates_title_length(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Case 1: Title too short (4 chars, min is 5)
        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Test',
            'initial_description' => 'Description with enough characters to pass validation.',
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');

        // Case 2: Title too long (300 chars, max is 255)
        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => str_repeat('A', 300),
            'initial_description' => 'Description with enough characters to pass validation.',
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    /**
     * Test #7: Validates description length (min 10, max 5000)
     *
     * Verifies initial_description validation constraints:
     * - Minimum 10 characters (should fail)
     * - Maximum 5000 characters (should fail)
     *
     * Expected: 422 for invalid lengths
     */
    #[Test]
    public function validates_description_length(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Case 1: Description too short (5 chars, min is 10)
        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Valid title with enough characters',
            'initial_description' => 'Short',
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('initial_description');

        // Case 2: Description too long (6000 chars, max is 5000)
        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Valid title with enough characters',
            'initial_description' => str_repeat('A', 6000),
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('initial_description');
    }

    // ==================== GROUP 4: Validación Existencia (Tests 8-9) ====================

    /**
     * Test #8: Validates company exists
     *
     * Verifies that company_id must reference an existing company.
     *
     * Expected: 422 with validation error for 'company_id'
     */
    #[Test]
    public function validates_company_exists(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $category = Category::factory()->create(['is_active' => true]);

        $fakeCompanyId = Str::uuid()->toString();

        $payload = [
            'company_id' => $fakeCompanyId,
            'category_id' => $category->id,
            'title' => 'Ticket con empresa inexistente',
            'initial_description' => 'Esta empresa no existe en la base de datos.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_id']);
    }

    /**
     * Test #9: Validates category exists and is active
     *
     * Verifies that category_id must reference an existing AND active category.
     * Inactive categories should not be allowed.
     *
     * Expected: 422 with validation error for 'category_id'
     */
    #[Test]
    public function validates_category_exists_and_is_active(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();

        // Case 1: Category does not exist
        $fakeCategoryId = Str::uuid()->toString();
        $payload = [
            'company_id' => $company->id,
            'category_id' => $fakeCategoryId,
            'title' => 'Ticket con categoría inexistente',
            'initial_description' => 'Esta categoría no existe en la base de datos.',
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);

        // Case 2: Category exists but is inactive
        $inactiveCategory = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => false,
        ]);
        $payload = [
            'company_id' => $company->id,
            'category_id' => $inactiveCategory->id,
            'title' => 'Ticket con categoría inactiva',
            'initial_description' => 'Esta categoría existe pero está inactiva.',
        ];
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    // ==================== GROUP 5: Permisos Especiales (Test 10) ====================

    /**
     * Test #10: User can create ticket in any company
     *
     * Verifies that USER role can create tickets in any company,
     * without restrictions based on "following" companies.
     *
     * Expected: Both tickets created successfully (201)
     */
    #[Test]
    public function user_can_create_ticket_in_any_company(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);
        $categoryA = Category::factory()->create([
            'company_id' => $companyA->id,
            'is_active' => true,
        ]);
        $categoryB = Category::factory()->create([
            'company_id' => $companyB->id,
            'is_active' => true,
        ]);

        // Act - Create ticket in Company A
        $payloadA = [
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'title' => 'Ticket en Empresa A',
            'initial_description' => 'Este ticket es para la empresa A.',
        ];
        $responseA = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payloadA);

        // Act - Create ticket in Company B
        $payloadB = [
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'title' => 'Ticket en Empresa B',
            'initial_description' => 'Este ticket es para la empresa B.',
        ];
        $responseB = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payloadB);

        // Assert - Both should succeed
        $responseA->assertStatus(201);
        $responseB->assertStatus(201);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket en Empresa A',
            'company_id' => $companyA->id,
            'created_by_user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket en Empresa B',
            'company_id' => $companyB->id,
            'created_by_user_id' => $user->id,
        ]);
    }

    // ==================== GROUP 6: Generación de Código (Tests 11-12) ====================

    /**
     * Test #11: Ticket code is generated automatically
     *
     * Verifies that ticket_code is auto-generated in format TKT-YYYY-XXXXX
     * when creating a ticket (user does not provide it).
     *
     * Expected: ticket_code matches pattern TKT-2025-XXXXX
     */
    #[Test]
    public function ticket_code_is_generated_automatically(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con código automático',
            'initial_description' => 'El código del ticket debe generarse automáticamente.',
            // Intentionally NOT including ticket_code
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['ticket_code']]);

        $ticketCode = $response->json('data.ticket_code');
        $currentYear = now()->year;

        // Verify format: TKT-YYYY-XXXXX
        $this->assertMatchesRegularExpression(
            "/^TKT-{$currentYear}-\d{5}$/",
            $ticketCode,
            "Ticket code should match pattern TKT-{$currentYear}-XXXXX"
        );
    }

    /**
     * Test #12: Ticket code is sequential per year
     *
     * Verifies that ticket codes are sequential within the same year:
     * - First ticket: TKT-2025-00001
     * - Second ticket: TKT-2025-00002
     *
     * Expected: Sequential numbering
     */
    #[Test]
    public function ticket_code_is_sequential_per_year(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Act - Create first ticket
        $payload1 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Primer ticket del año',
            'initial_description' => 'Este debería ser TKT-2025-00001.',
        ];
        $response1 = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload1);

        // Act - Create second ticket
        $payload2 = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Segundo ticket del año',
            'initial_description' => 'Este debería ser TKT-2025-00002.',
        ];
        $response2 = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload2);

        // Assert
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $ticketCode1 = $response1->json('data.ticket_code');
        $ticketCode2 = $response2->json('data.ticket_code');

        $currentYear = now()->year;

        // Verify sequential codes
        $this->assertEquals("TKT-{$currentYear}-00001", $ticketCode1);
        $this->assertEquals("TKT-{$currentYear}-00002", $ticketCode2);
    }

    // ==================== GROUP 7: Estados Iniciales (Tests 13-14) ====================

    /**
     * Test #13: Ticket starts with status 'open'
     *
     * Verifies that newly created tickets automatically have status = 'open'.
     *
     * Expected: status = 'open'
     */
    #[Test]
    public function ticket_starts_with_status_open(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con status inicial open',
            'initial_description' => 'El status inicial debe ser "open".',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con status inicial open',
            'status' => 'open',
        ]);
    }

    /**
     * Test #14: created_by_user_id is set to authenticated user
     *
     * Verifies that the created_by_user_id field is automatically set
     * to the authenticated user's ID.
     *
     * Expected: created_by_user_id matches authenticated user
     */
    #[Test]
    public function created_by_user_id_is_set_to_authenticated_user(): void
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket con created_by_user_id automático',
            'initial_description' => 'El created_by_user_id debe ser del usuario autenticado.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('data.created_by_user_id', $user->id);

        $this->assertDatabaseHas('ticketing.tickets', [
            'title' => 'Ticket con created_by_user_id automático',
            'created_by_user_id' => $user->id,
        ]);
    }

    // ==================== GROUP 8: Eventos (Test 15) ====================

    /**
     * Test #15: Ticket creation triggers event
     *
     * Verifies that creating a ticket triggers a TicketCreated event.
     *
     * Expected: TicketCreated event is dispatched
     */
    #[Test]
    public function ticket_creation_triggers_event(): void
    {
        // Arrange
        Event::fake();

        $user = User::factory()->withRole('USER')->create();
        $company = Company::factory()->create();
        $category = Category::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $payload = [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Ticket que dispara evento',
            'initial_description' => 'Este ticket debería disparar un evento TicketCreated.',
        ];

        // Act
        $response = $this->authenticateWithJWT($user)
            ->postJson('/api/v1/tickets', $payload);

        // Assert
        $response->assertStatus(201);

        // Verify event was dispatched
        Event::assertDispatched(\App\Features\TicketManagement\Events\TicketCreated::class);
    }
}
