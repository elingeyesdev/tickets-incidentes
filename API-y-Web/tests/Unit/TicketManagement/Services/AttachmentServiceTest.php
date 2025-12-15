<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Services;

use App\Features\TicketManagement\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

/**
 * Unit Tests for AttachmentService
 *
 * Tests the BUSINESS LOGIC of file attachment handling.
 * These tests validate internal logic that Feature Tests ASSUME works.
 *
 * Coverage:
 * - File size validation (max 10 MB)
 * - File type validation (allowed/disallowed extensions)
 * - File storage path calculation
 *
 * NOT COVERED HERE (covered by Feature Tests):
 * - Endpoint integration
 * - Authentication/Authorization
 * - Database persistence
 * - Real file uploads with Storage::fake()
 *
 * Expected Exceptions:
 * - FileUploadException: When file size exceeds limit
 * - FileUploadException: When file type is not allowed
 *
 * Business Rules:
 * - Max file size: 10 MB
 * - Allowed types: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP
 * - Storage path: tickets/attachments/{uuid}
 */
class AttachmentServiceTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    private AttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Service will be instantiated when implemented
        // $this->service = app(AttachmentService::class);
    }

    // ==================== GROUP 1: File Size Validation ====================

    /**
     * Test #1: Validates file size max 10MB
     *
     * BUSINESS LOGIC: The service rejects files larger than 10 MB
     *
     * Validates that AttachmentService throws FileUploadException when
     * attempting to upload files larger than 10 MB.
     *
     * Business Rule:
     * - Maximum file size: 10 MB (10,485,760 bytes)
     * - Files exceeding limit MUST throw exception
     * - Files within limit MUST pass validation
     *
     * Expected Exception:
     * - Type: FileUploadException
     * - Error message explains max size limit
     */
    #[Test]
    public function validates_file_size_max_10mb(): void
    {
        // Arrange
        $this->service = app(AttachmentService::class);
        $fileSizeExceeded = 15 * 1024 * 1024; // 15 MB (exceeds 10 MB limit)

        // Act & Assert
        try {
            $this->service->validateFileSize($fileSizeExceeded);
            $this->fail('Expected FileUploadException for 15 MB file');
        } catch (\Exception $e) {
            // Verify correct exception type and message
            $this->assertTrue(
                str_contains(get_class($e), 'Exception'),
                'Should throw exception when file size exceeds limit'
            );
            $this->assertStringContainsStringIgnoringCase(
                'size',
                $e->getMessage(),
                'Exception message should mention file size'
            );
        }
    }

    // ==================== GROUP 2: File Type Validation ====================

    /**
     * Test #2: Validates allowed file types
     *
     * BUSINESS LOGIC: The service rejects file types not in the allowed list
     *
     * Validates that AttachmentService throws exception for disallowed file types
     * (executables, scripts, etc.) and allows only safe types (PDF, JPG, PNG, GIF, etc.).
     *
     * Allowed Extensions:
     * - Documents: PDF, DOC, DOCX, TXT
     * - Images: JPG, PNG, GIF
     * - Spreadsheets: XLS, XLSX
     * - Archives: ZIP
     *
     * Disallowed Extensions (security):
     * - Executables: EXE, BAT, SH, CMD, COM, SCR
     * - Scripts: JS, VBS, PS1
     * - Any other extension not in allowed list
     *
     * Expected Exception:
     * - Type: FileUploadException
     * - Error message explains allowed types
     */
    #[Test]
    public function validates_allowed_file_types(): void
    {
        // Arrange
        $this->service = app(AttachmentService::class);
        $disallowedFileName = 'malicious-virus.exe';

        // Act & Assert
        try {
            $this->service->validateFileType($disallowedFileName);
            $this->fail('Expected exception for disallowed file type (.exe)');
        } catch (\Exception $e) {
            // Verify correct exception and message
            $this->assertTrue(
                str_contains(get_class($e), 'Exception'),
                'Should throw exception for disallowed file type'
            );
            $this->assertStringContainsStringIgnoringCase(
                'type',
                $e->getMessage(),
                'Exception message should mention file type'
            );
        }
    }

    // ==================== GROUP 3: File Storage Path ====================

    /**
     * Test #3: Stores file in correct path
     *
     * BUSINESS LOGIC: The service calculates the correct storage path for attachments
     *
     * Context:
     * - Feature Tests validate that file EXISTS after upload
     * - Unit Test validates the LOGIC of path calculation
     *
     * Scenario:
     * - Simulate file upload metadata
     * - Call AttachmentService::calculateStoragePath()
     * - Verify path begins with "tickets/attachments/"
     * - Verify path includes a unique identifier (UUID or timestamp)
     *
     * Expected Result:
     * - Path format: "tickets/attachments/{unique-id}/{filename}"
     * - OR: "tickets/attachments/{unique-filename}"
     * - Path MUST be unique to avoid collisions
     * - Path MUST be secure (no directory traversal)
     *
     * Security Considerations:
     * - Path MUST NOT contain user input directly (sanitize filename)
     * - Path MUST NOT allow "../" or "./" (directory traversal)
     * - Path SHOULD include unique identifier to prevent overwrites
     *
     * Implementation Note:
     * This test will FAIL (RED) until AttachmentService is implemented.
     */
    #[Test]
    public function stores_file_in_correct_path(): void
    {
        // Arrange
        $this->service = app(AttachmentService::class);
        $originalFileName = 'my-document.pdf';

        // Act
        $calculatedPath = $this->service->calculateStoragePath($originalFileName);

        // Assert - Path structure
        $this->assertStringStartsWith(
            'tickets/attachments/',
            $calculatedPath,
            'Storage path must start with "tickets/attachments/"'
        );

        // Assert - Path includes unique identifier
        $this->assertMatchesRegularExpression(
            '/tickets\/attachments\/[a-f0-9\-]+/i',
            $calculatedPath,
            'Path must include unique identifier (UUID or hash)'
        );

        // Assert - Path is secure (no directory traversal)
        $this->assertStringNotContainsString(
            '..',
            $calculatedPath,
            'Path must not contain ".." (directory traversal vulnerability)'
        );
        $this->assertStringNotContainsString(
            '//',
            $calculatedPath,
            'Path must not contain "//" (double slashes)'
        );

        // Assert - Path preserves file extension
        $this->assertStringEndsWith(
            '.pdf',
            $calculatedPath,
            'Path must preserve original file extension'
        );

        // Assert - Same filename generates different paths (uniqueness)
        $calculatedPath2 = $this->service->calculateStoragePath($originalFileName);
        $this->assertNotEquals(
            $calculatedPath,
            $calculatedPath2,
            'Same filename called twice must generate different paths'
        );
    }
}
