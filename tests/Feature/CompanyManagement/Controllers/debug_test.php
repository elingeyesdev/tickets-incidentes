<?php
use App\Features\UserManagement\Models\User;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_company_index()
    {
        $admin = User::factory()->withRole('PLATFORM_ADMIN')->create();
        Company::factory()->create();

        $response = $this->authenticateWithJWT($admin)->getJson('/api/companies');
        
        echo "\n\n=== DEBUG OUTPUT ===\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response:\n";
        echo $response->content() . "\n";
        echo "==================\n\n";
        
        $this->assertEquals(200, $response->status());
    }
}
