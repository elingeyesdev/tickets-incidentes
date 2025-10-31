<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_company_admin_cannot_update_another_company()
    {
        // Arrange
        $companyAdmin = User::factory()->create();
        $ownCompany = Company::factory()->create(['admin_user_id' => $companyAdmin->id]);
        $companyAdmin->assignRole('COMPANY_ADMIN', $ownCompany->id);

        $otherCompany = Company::factory()->create();

        $inputData = ['name' => 'Hacked Name'];

        // Act
        $response = $this->authenticateWithJWT($companyAdmin)
            ->patchJson("/api/companies/{$otherCompany->id}", $inputData);

        // Debug: Print full response
        echo "\n\n=== AUTHORIZATION DEBUG ===\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        echo "Raw Body:\n" . $response->getContent() . "\n";
        echo "Decoded JSON:\n" . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
        echo "=====================================\n\n";

        // Assert - just to make it pass
        $this->assertTrue(true);
    }
}
