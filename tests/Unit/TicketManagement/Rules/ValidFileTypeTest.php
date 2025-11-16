<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Rules;

use App\Features\TicketManagement\Rules\ValidFileType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit Tests for ValidFileType Rule
 *
 * Tests the custom Laravel validation rule for file type validation.
 * This rule validates that uploaded files are of allowed types and rejects
 * potentially dangerous executable files.
 *
 * Coverage:
 * - Validates all allowed file types (16 total: PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, WEBP, SVG, MP4)
 * - Rejects executable and script files (.exe, .sh, .bat, .com, .scr)
 * - Provides descriptive error messages listing allowed types
 *
 * Allowed Types (16 total):
 * - Documents (8): PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV
 * - Images (7): JPG, JPEG, PNG, GIF, BMP, WEBP, SVG
 * - Videos (1): MP4
 *
 * Rejected Types (Security):
 * - Executables: EXE, COM, SCR
 * - Scripts: SH, BAT
 */
class ValidFileTypeTest extends TestCase
{
    /**
     * Test #1: Validates all allowed file types
     *
     * Verifies that the ValidFileType rule accepts all permitted file types.
     * This consolidates testing all allowed types in a single comprehensive test.
     *
     * Allowed types (16 total): PDF, TXT, LOG, DOC, DOCX, XLS, XLSX, CSV, JPG, JPEG, PNG, GIF, BMP, WEBP, SVG, MP4
     * NOTE: ZIP is NOT allowed (security risk - vector of attack)
     *
     * Expected: true for all allowed types
     */
    #[Test]
    public function validates_all_allowed_file_types(): void
    {
        // Arrange
        $rule = new ValidFileType();

        // 16 allowed types matching UploadAttachmentTest Feature Test
        $allowedTypes = [
            // Documentos (8)
            'pdf' => 'document.pdf',
            'txt' => 'notes.txt',
            'log' => 'application.log',
            'doc' => 'report.doc',
            'docx' => 'report.docx',
            'xls' => 'spreadsheet.xls',
            'xlsx' => 'spreadsheet.xlsx',
            'csv' => 'data.csv',

            // Imágenes (7)
            'jpg' => 'image.jpg',
            'jpeg' => 'photo.jpeg',
            'png' => 'screenshot.png',
            'gif' => 'animation.gif',
            'bmp' => 'bitmap.bmp',
            'webp' => 'modern.webp',
            'svg' => 'icon.svg',

            // Videos (1)
            'mp4' => 'demo.mp4',
        ];

        // Act & Assert
        foreach ($allowedTypes as $extension => $fileName) {
            $file = UploadedFile::fake()->create($fileName, 100);
            $validator = Validator::make(['file' => $file], ['file' => $rule]);

            $this->assertTrue(
                $validator->passes(),
                "File type {$extension} should be allowed but was rejected"
            );
        }
    }

    /**
     * Test #2: Rejects executable and script files
     *
     * Verifies that the ValidFileType rule rejects potentially dangerous file types
     * including executables and scripts for security reasons.
     *
     * Rejected types: EXE, SH, BAT, COM, SCR
     *
     * Expected: false for all executable/script types
     */
    #[Test]
    public function rejects_executable_and_script_files(): void
    {
        // Arrange
        $rule = new ValidFileType();

        $maliciousTypes = [
            'exe' => 'malware.exe',
            'sh' => 'script.sh',
            'bat' => 'command.bat',
            'com' => 'program.com',
            'scr' => 'screensaver.scr',
        ];

        // Act & Assert
        foreach ($maliciousTypes as $extension => $fileName) {
            $file = UploadedFile::fake()->create($fileName, 100);
            $validator = Validator::make(['file' => $file], ['file' => $rule]);

            $this->assertFalse(
                $validator->passes(),
                "File type {$extension} should be rejected for security but was allowed"
            );
        }
    }

    /**
     * Test #3: Error message lists allowed types
     *
     * Verifies that the error message returned by the rule is descriptive
     * and includes a list of all allowed file types (16 total).
     *
     * Expected: Message contains list of all 16 allowed types
     */
    #[Test]
    public function error_message_lists_allowed_types(): void
    {
        // Arrange
        $rule = new ValidFileType();
        $maliciousFile = UploadedFile::fake()->create('unknown.xyz', 100); // Tipo no permitido

        // Act - Trigger validation failure
        $validator = Validator::make(['file' => $maliciousFile], ['file' => $rule]);
        $validator->fails(); // Execute validation to generate error messages
        $message = $validator->errors()->first('file');

        // Assert - Message should be descriptive
        $this->assertIsString($message, 'Error message should be a string');

        // Message should mention all 16 allowed types
        $expectedTypes = [
            'pdf', 'txt', 'log', 'doc', 'docx', 'xls', 'xlsx', 'csv',  // Documentos
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',          // Imágenes
            'mp4'                                                         // Videos
        ];

        foreach ($expectedTypes as $type) {
            $this->assertStringContainsStringIgnoringCase(
                $type,
                $message,
                "Error message should mention allowed type: {$type}"
            );
        }
    }
}
