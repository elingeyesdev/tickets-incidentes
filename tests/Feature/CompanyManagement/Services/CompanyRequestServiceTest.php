<?php

namespace Tests\Feature\CompanyManagement\Services;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Services\CompanyRequestService;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite completo para CompanyRequestService
 *
 * Tests unitarios:
 * - submit() - Crea solicitud con request_code único
 * - approve() - Crea empresa, usuario, asigna rol
 * - reject() - Marca como rechazada con razón
 * - getPending() - Retorna solo pendientes
 * - getAll() - Retorna todas o filtradas por status
 * - hasPendingRequest() - Verifica email duplicado
 */
class CompanyRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompanyRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CompanyRequestService::class);
    }

    /** @test */
    public function submit_creates_request_with_unique_request_code()
    {
        // Arrange
        $data = [
            'company_name' => 'Test Company',
            'legal_name' => 'Test Company SRL',
            'admin_email' => 'admin@test.com',
            'business_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.',
            'industry_type' => 'Technology',
        ];

        // Act
        $request = $this->service->submit($data);

        // Assert
        $this->assertInstanceOf(CompanyRequest::class, $request);
        $this->assertEquals('Test Company', $request->company_name);
        $this->assertEquals('admin@test.com', $request->admin_email);
        $this->assertEquals('pending', $request->status);
        $this->assertNotEmpty($request->request_code);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $request->request_code);

        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'company_name' => 'Test Company',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function approve_creates_company_user_and_assigns_role()
    {
        // Arrange
        $reviewer = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'company_name' => 'New Company',
            'admin_email' => 'newadmin@company.com',
        ]);

        // Verificar que no existe empresa ni usuario
        $this->assertDatabaseMissing('business.companies', [
            'name' => 'New Company',
        ]);

        // Act
        $company = $this->service->approve($request, $reviewer);

        // Assert
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('New Company', $company->name);
        $this->assertEquals('active', $company->status);

        // Verificar que usuario fue creado
        $this->assertDatabaseHas('auth.users', [
            'email' => 'newadmin@company.com',
        ]);

        // Verificar que rol fue asignado
        $adminUser = User::where('email', 'newadmin@company.com')->first();
        $this->assertDatabaseHas('auth.user_roles', [
            'user_id' => $adminUser->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Verificar que request fue marcado como approved
        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals($reviewer->id, $request->reviewed_by_id);
        $this->assertNotNull($request->reviewed_at);
    }

    /** @test */
    public function approve_uses_existing_user_if_email_exists()
    {
        // Arrange
        $reviewer = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $existingUser = User::factory()->create(['email' => 'existing@company.com']);

        $request = CompanyRequest::factory()->create([
            'status' => 'pending',
            'admin_email' => 'existing@company.com',
        ]);

        $initialUserCount = User::count();

        // Act
        $company = $this->service->approve($request, $reviewer);

        // Assert - No se creó nuevo usuario
        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($existingUser->id, $company->admin_user_id);
    }

    /** @test */
    public function approve_throws_exception_when_request_not_pending()
    {
        // Arrange
        $reviewer = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'approved']);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending requests can be approved');

        $this->service->approve($request, $reviewer);
    }

    /** @test */
    public function reject_marks_request_as_rejected_with_reason()
    {
        // Arrange
        $reviewer = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'pending']);
        $reason = 'Business information is insufficient';

        // Act
        $rejectedRequest = $this->service->reject($request, $reviewer, $reason);

        // Assert
        $this->assertEquals('rejected', $rejectedRequest->status);
        $this->assertEquals($reason, $rejectedRequest->rejection_reason);
        $this->assertEquals($reviewer->id, $rejectedRequest->reviewed_by_id);
        $this->assertNotNull($rejectedRequest->reviewed_at);

        $this->assertDatabaseHas('business.company_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /** @test */
    public function reject_throws_exception_when_request_not_pending()
    {
        // Arrange
        $reviewer = User::factory()->withRole('PLATFORM_ADMIN')->create();
        $request = CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending requests can be rejected');

        $this->service->reject($request, $reviewer, 'Test reason');
    }

    /** @test */
    public function get_pending_returns_only_pending_requests()
    {
        // Arrange
        CompanyRequest::factory()->count(3)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(2)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act
        $pending = $this->service->getPending();

        // Assert
        $this->assertCount(3, $pending);

        foreach ($pending as $request) {
            $this->assertEquals('pending', $request->status);
        }
    }

    /** @test */
    public function get_pending_respects_limit()
    {
        // Arrange
        CompanyRequest::factory()->count(20)->create(['status' => 'pending']);

        // Act
        $pending = $this->service->getPending(10);

        // Assert
        $this->assertCount(10, $pending);
    }

    /** @test */
    public function get_all_returns_all_requests_when_no_status_filter()
    {
        // Arrange
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);
        CompanyRequest::factory()->create(['status' => 'rejected']);

        // Act
        $all = $this->service->getAll();

        // Assert
        $this->assertCount(6, $all);
    }

    /** @test */
    public function get_all_filters_by_status_when_provided()
    {
        // Arrange
        CompanyRequest::factory()->count(2)->create(['status' => 'pending']);
        CompanyRequest::factory()->count(3)->create(['status' => 'approved']);

        // Act
        $approved = $this->service->getAll('approved');

        // Assert
        $this->assertCount(3, $approved);

        foreach ($approved as $request) {
            $this->assertEquals('approved', $request->status);
        }
    }

    /** @test */
    public function has_pending_request_returns_true_when_pending_exists()
    {
        // Arrange
        CompanyRequest::factory()->create([
            'admin_email' => 'pending@example.com',
            'status' => 'pending',
        ]);

        // Act
        $result = $this->service->hasPendingRequest('pending@example.com');

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function has_pending_request_returns_false_when_no_pending()
    {
        // Arrange
        CompanyRequest::factory()->create([
            'admin_email' => 'approved@example.com',
            'status' => 'approved',
        ]);

        // Act
        $result = $this->service->hasPendingRequest('approved@example.com');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function has_pending_request_returns_false_when_email_not_found()
    {
        // Act
        $result = $this->service->hasPendingRequest('nonexistent@example.com');

        // Assert
        $this->assertFalse($result);
    }
}
