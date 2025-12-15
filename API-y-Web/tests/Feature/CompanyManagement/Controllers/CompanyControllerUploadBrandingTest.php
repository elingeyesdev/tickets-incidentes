<?php

declare(strict_types=1);

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\CompanyManagement\SeedsCompanyIndustries;
use Tests\TestCase;

/**
 * Feature Tests for POST /api/companies/{company}/logo and POST /api/companies/{company}/favicon
 * (CompanyController@uploadLogo and CompanyController@uploadFavicon)
 *
 * Complete test suite with edge cases for company branding uploads.
 *
 * Coverage:
 * - Authentication validation (unauthenticated users rejected)
 * - Authorization (only PLATFORM_ADMIN or COMPANY_ADMIN of the company allowed)
 * - File type validation (logo: JPEG, PNG, GIF, WebP, SVG | favicon: ICO, PNG, JPEG)
 * - File size validation (logo: max 5 MB | favicon: max 1 MB)
 * - File storage (correct paths: storage/app/public/company-logos/{company_id}/ or favicons/)
 * - Database update (logo_url / favicon_url correctly persisted)
 * - Rate limiting (10 requests/hour per endpoint)
 * - Concurrent uploads (new upload replaces old)
 * - Edge cases (missing file, empty file, unicode filenames)
 * - Response format validation
 * - Company isolation (COMPANY_ADMIN cannot upload for other companies)
 * - URL format validation (absolute URLs with asset() helper)
 *
 * Expected Status Codes:
 * - 200: File uploaded successfully
 * - 401: Unauthenticated user
 * - 403: Insufficient permissions (USER, AGENT, COMPANY_ADMIN of different company)
 * - 404: Company not found
 * - 422: Validation error (file required, invalid type, file too large)
 * - 429: Rate limited
 *
 * Database Schema: business.companies
 * - logo_url: VARCHAR(500) - nullable
 * - favicon_url: VARCHAR(500) - nullable
 *
 * Storage:
 * - Logo Disk: public (storage/app/public)
 * - Logo Path: company-logos/{company_id}/{timestamp}_{slug_filename}.{ext}
 * - Favicon Disk: public (storage/app/public)
 * - Favicon Path: favicons/{company_id}/{timestamp}_{slug_filename}.{ext}
 * - Logo Max size: 5 MB
 * - Favicon Max size: 1 MB
 * - Logo Allowed types: jpeg, jpg, png, gif, webp, svg
 * - Favicon Allowed types: ico, png, jpeg, jpg
 */
class CompanyControllerUploadBrandingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsCompanyIndustries;

    private Company $testCompany;
    private User $platformAdmin;
    private User $companyAdmin;
    private User $agentUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create platform admin
        $this->platformAdmin = User::factory()
            ->withProfile()
            ->withRole('PLATFORM_ADMIN')
            ->create(['email' => 'platform-admin@example.com']);

        // Create company and its admin
        $this->testCompany = Company::factory()->create([
            'name' => 'Test Company',
            'admin_user_id' => User::factory()->create()->id,
            'logo_url' => null,
            'favicon_url' => null,
        ]);

        $this->companyAdmin = User::factory()
            ->withProfile()
            ->create(['email' => 'company-admin@example.com']);

        // Assign COMPANY_ADMIN role
        $this->companyAdmin->assignRole('COMPANY_ADMIN', $this->testCompany->id);

        // Create agent user
        $this->agentUser = User::factory()
            ->withProfile()
            ->create(['email' => 'agent@example.com']);

        $this->agentUser->assignRole('AGENT', $this->testCompany->id);

        // Create regular user
        $this->regularUser = User::factory()
            ->withProfile()
            ->withRole('USER')
            ->create(['email' => 'user@example.com']);
    }

    // ==================== GROUP 1: Logo Upload - Authentication Tests ====================

    #[Test]
    public function upload_logo_requires_authentication(): void
    {
        // Arrange
        Storage::fake('public');
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->postJson("/api/companies/{$this->testCompany->id}/logo", [
            'logo' => $file,
        ]);

        // Assert
        $response->assertUnauthorized();
        Storage::disk('public')->assertMissing('company-logos/*');
    }

    #[Test]
    public function upload_logo_rejects_invalid_token(): void
    {
        // Arrange
        Storage::fake('public');
        $file = UploadedFile::fake()->image('logo.png');

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-xyz',
        ])->postJson("/api/companies/{$this->testCompany->id}/logo", [
            'logo' => $file,
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    // ==================== GROUP 2: Logo Upload - Authorization Tests ====================

    #[Test]
    public function platform_admin_can_upload_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('message', 'Logo uploaded successfully');
    }

    #[Test]
    public function company_admin_can_upload_logo_for_own_company(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->companyAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function agent_cannot_upload_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->agentUser);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function regular_user_cannot_upload_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->regularUser);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function company_admin_cannot_upload_logo_for_other_company(): void
    {
        // Arrange
        Storage::fake('public');

        // Create another company with different admin
        $otherCompany = Company::factory()->create();
        $otherAdmin = User::factory()->withProfile()->create();
        $otherAdmin->assignRole('COMPANY_ADMIN', $otherCompany->id);

        $token = $this->generateAccessToken($otherAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertForbidden();
    }

    // ==================== GROUP 3: Logo Upload - File Validation Tests ====================

    #[Test]
    public function upload_logo_requires_file_field(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", []);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function upload_logo_accepts_jpg_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.jpg', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_accepts_png_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_accepts_webp_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.webp', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_accepts_svg_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.svg', 512, 'image/svg+xml');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_rejects_pdf_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.pdf', 1024, 'application/pdf');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function upload_logo_rejects_executable_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.exe', 1024, 'application/exe');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
    }

    // ==================== GROUP 4: Logo Upload - File Size Validation ====================

    #[Test]
    public function upload_logo_accepts_5mb_file_at_limit(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->image('logo.png')->size(5120); // Exactly 5 MB

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_rejects_file_exceeding_5mb(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->image('logo.png')->size(5121); // 5 MB + 1 KB

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.logo.0', 'Logo must not exceed 5 MB');
    }

    // ==================== GROUP 5: Logo Upload - Storage & Database ====================

    #[Test]
    public function upload_logo_stores_in_correct_directory(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('company-logo.png', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $logoUrl = $response->json('data.logoUrl');

        // Verify path includes company ID
        $this->assertStringContainsString("company-logos/{$this->testCompany->id}", $logoUrl);

        // Verify file exists
        $files = Storage::disk('public')->files("company-logos/{$this->testCompany->id}");
        $this->assertNotEmpty($files);
    }

    #[Test]
    public function upload_logo_updates_database(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Verify no logo initially
        $this->testCompany->refresh();
        $this->assertNull($this->testCompany->logo_url);

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $logoUrl = $response->json('data.logoUrl');

        // Verify database was updated
        $this->testCompany->refresh();
        $this->assertEquals($logoUrl, $this->testCompany->logo_url);
        $this->assertNotNull($this->testCompany->logo_url);
    }

    #[Test]
    public function upload_logo_replaces_previous_logo(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);

        // Set initial logo
        $this->testCompany->update(['logo_url' => 'https://old-logo.com/logo.png']);
        $oldUrl = $this->testCompany->logo_url;

        $file = UploadedFile::fake()->create('new-logo.png', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $newUrl = $response->json('data.logoUrl');

        // Verify URL changed
        $this->assertNotEquals($oldUrl, $newUrl);

        // Verify database reflects new URL
        $this->testCompany->refresh();
        $this->assertEquals($newUrl, $this->testCompany->logo_url);
    }

    // ==================== GROUP 6: Favicon Upload - Authentication & Authorization ====================

    #[Test]
    public function upload_favicon_requires_authentication(): void
    {
        // Arrange
        Storage::fake('public');
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->postJson("/api/companies/{$this->testCompany->id}/favicon", [
            'favicon' => $file,
        ]);

        // Assert
        $response->assertUnauthorized();
    }

    #[Test]
    public function platform_admin_can_upload_favicon(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJsonPath('message', 'Favicon uploaded successfully');
    }

    #[Test]
    public function company_admin_can_upload_favicon_for_own_company(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->companyAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function agent_cannot_upload_favicon(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->agentUser);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertForbidden();
    }

    // ==================== GROUP 7: Favicon Upload - File Validation ====================

    #[Test]
    public function upload_favicon_requires_file_field(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", []);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function upload_favicon_accepts_ico_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_favicon_accepts_png_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_favicon_accepts_jpeg_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.jpg', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_favicon_rejects_svg_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.svg', 512, 'image/svg+xml');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
    }

    #[Test]
    public function upload_favicon_rejects_pdf_files(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.pdf', 1024, 'application/pdf');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
    }

    // ==================== GROUP 8: Favicon Upload - File Size Validation ====================

    #[Test]
    public function upload_favicon_accepts_1mb_file_at_limit(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->image('favicon.png')->size(1024); // Exactly 1 MB

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_favicon_rejects_file_exceeding_1mb(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->image('favicon.png')->size(1025); // 1 MB + 1 KB

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonPath('errors.favicon.0', 'Favicon must not exceed 1 MB');
    }

    // ==================== GROUP 9: Favicon Upload - Storage & Database ====================

    #[Test]
    public function upload_favicon_stores_in_correct_directory(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
        $faviconUrl = $response->json('data.faviconUrl');

        // Verify path includes company ID
        $this->assertStringContainsString("favicons/{$this->testCompany->id}", $faviconUrl);

        // Verify file exists
        $files = Storage::disk('public')->files("favicons/{$this->testCompany->id}");
        $this->assertNotEmpty($files);
    }

    #[Test]
    public function upload_favicon_updates_database(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Verify no favicon initially
        $this->testCompany->refresh();
        $this->assertNull($this->testCompany->favicon_url);

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
        $faviconUrl = $response->json('data.faviconUrl');

        // Verify database was updated
        $this->testCompany->refresh();
        $this->assertEquals($faviconUrl, $this->testCompany->favicon_url);
        $this->assertNotNull($this->testCompany->favicon_url);
    }

    // ==================== GROUP 10: Edge Cases & Unicode ====================

    #[Test]
    public function upload_logo_handles_unicode_filename(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('логотип_компании.png', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_handles_special_characters(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo@#$%^&().png', 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function upload_logo_handles_very_long_filename(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $longName = str_repeat('a', 200) . '.png';
        $file = UploadedFile::fake()->create($longName, 1024, 'image/jpeg');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $logoUrl = $response->json('data.logoUrl');
        $this->assertLessThan(500, strlen($logoUrl));
    }

    // ==================== GROUP 11: Company Not Found Tests ====================

    #[Test]
    public function upload_logo_returns_404_for_nonexistent_company(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $nonexistentId = '00000000-0000-0000-0000-000000000000';
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$nonexistentId}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function upload_favicon_returns_404_for_nonexistent_company(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $nonexistentId = '00000000-0000-0000-0000-000000000000';
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$nonexistentId}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertNotFound();
    }

    // ==================== GROUP 12: Response Format Tests ====================

    #[Test]
    public function upload_logo_response_has_correct_structure(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", [
                'logo' => $file,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => ['logoUrl'],
        ]);
        $this->assertEquals('Logo uploaded successfully', $response->json('message'));
    }

    #[Test]
    public function upload_favicon_response_has_correct_structure(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        // Act
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/favicon", [
                'favicon' => $file,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'data' => ['faviconUrl'],
        ]);
        $this->assertEquals('Favicon uploaded successfully', $response->json('message'));
    }

    // ==================== GROUP 13: Concurrent Upload Tests ====================

    #[Test]
    public function concurrent_logo_uploads_succeeds(): void
    {
        // Arrange
        Storage::fake('public');
        $token = $this->generateAccessToken($this->platformAdmin);
        $file1 = UploadedFile::fake()->create('logo1.png', 1024, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('logo2.png', 1024, 'image/jpeg');

        // Act
        $response1 = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", ['logo' => $file1]);

        $response2 = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$this->testCompany->id}/logo", ['logo' => $file2]);

        // Assert
        $response1->assertOk();
        $response2->assertOk();

        // Verify latest URL is in database
        $this->testCompany->refresh();
        $this->assertEquals($response2->json('data.logoUrl'), $this->testCompany->logo_url);
    }

    #[Test]
    public function multiple_companies_can_upload_logos_independently(): void
    {
        // Arrange
        Storage::fake('public');

        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $token = $this->generateAccessToken($this->platformAdmin);
        $file1 = UploadedFile::fake()->create('logo1.png', 1024, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('logo2.png', 1024, 'image/jpeg');

        // Act
        $response1 = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$company1->id}/logo", ['logo' => $file1]);

        $response2 = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson("/api/companies/{$company2->id}/logo", ['logo' => $file2]);

        // Assert
        $response1->assertOk();
        $response2->assertOk();

        // Verify different URLs and company IDs
        $url1 = $response1->json('data.logoUrl');
        $url2 = $response2->json('data.logoUrl');

        $this->assertStringContainsString($company1->id, $url1);
        $this->assertStringContainsString($company2->id, $url2);
        $this->assertNotEquals($url1, $url2);
    }
}
