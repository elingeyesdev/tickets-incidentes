<?php

namespace Tests\Feature\CompanyManagement\Controllers;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTestResponse extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_filter_followed_by_me()
    {
        // Arrange
        $user = User::factory()->withRole('USER')->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $company3 = Company::factory()->create();

        // Usuario sigue solo company1 y company2
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company1->id]);
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company2->id]);

        // Act - Try different parameter formats
        echo "\n\n=== Test 1: followed_by_me=true ===\n";
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?followed_by_me=true');
        echo "Status: " . $response->status() . "\n";
        if ($response->status() !== 200) {
            echo "Errors: " . json_encode($response->json('errors'), JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Success! Data count: " . count($response->json('data')) . "\n";
        }

        echo "\n=== Test 2: followed_by_me=1 ===\n";
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore?followed_by_me=1');
        echo "Status: " . $response->status() . "\n";
        if ($response->status() !== 200) {
            echo "Errors: " . json_encode($response->json('errors'), JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "Success! Data count: " . count($response->json('data')) . "\n";
        }

        echo "\n=== Test 3: No parameter ===\n";
        $response = $this->authenticateWithJWT($user)
            ->getJson('/api/companies/explore');
        echo "Status: " . $response->status() . "\n";
        if ($response->status() === 200) {
            echo "Success! Data count: " . count($response->json('data')) . "\n";
        }

        $this->assertTrue(true);
    }
}
