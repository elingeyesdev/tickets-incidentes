<?php
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest2 extends TestCase
{
    use RefreshDatabase;

    public function test_context_management_returns_all_fields()
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        Company::factory()->create();

        $response = $this->authenticateWithJWT($admin)->getJson('/api/companies');
        
        echo "\n\n=== INDEX ENDPOINT DEBUG ===\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response JSON structure:\n";
        var_dump($response->json());
        echo "============================\n\n";
        
        $this->assertEquals(200, $response->status());
    }
}
