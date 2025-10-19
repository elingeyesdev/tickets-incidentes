<?php

namespace Tests\Feature\CompanyManagement\DataLoaders;

use App\Features\CompanyManagement\GraphQL\DataLoaders\FollowersCountByCompanyIdBatchLoader;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FollowersCountByCompanyIdBatchLoaderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_loads_follower_count_for_a_single_company()
    {
        // Arrange
        $company = Company::factory()->create();
        $users = User::factory()->count(5)->create();

        // 5 users follow the company
        foreach ($users as $user) {
            CompanyFollower::create(['user_id' => $user->id, 'company_id' => $company->id]);
        }

        // Act
        $loader = app(FollowersCountByCompanyIdBatchLoader::class);
        $count = $loader->load($company->id);

        // Assert
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function it_returns_zero_for_company_with_no_followers()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $loader = app(FollowersCountByCompanyIdBatchLoader::class);
        $count = $loader->load($company->id);

        // Assert
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function it_batches_multiple_companies_in_single_query()
    {
        // Arrange
        $companies = Company::factory()->count(3)->create();
        $users = User::factory()->count(10)->create();

        // Company 1: 3 followers
        for ($i = 0; $i < 3; $i++) {
            CompanyFollower::create(['user_id' => $users[$i]->id, 'company_id' => $companies[0]->id]);
        }

        // Company 2: 5 followers
        for ($i = 3; $i < 8; $i++) {
            CompanyFollower::create(['user_id' => $users[$i]->id, 'company_id' => $companies[1]->id]);
        }

        // Company 3: 0 followers

        // Act
        DB::enableQueryLog();
        $loader = app(FollowersCountByCompanyIdBatchLoader::class);

        $deferred1 = $loader->load($companies[0]->id);
        $deferred2 = $loader->load($companies[1]->id);
        $deferred3 = $loader->load($companies[2]->id);

        // Resolve all deferred values
        $count1 = $deferred1->then(fn($v) => $v)->wait();
        $count2 = $deferred2->then(fn($v) => $v)->wait();
        $count3 = $deferred3->then(fn($v) => $v)->wait();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert - Should be only 1 query with GROUP BY for all companies
        $countQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_company_followers') !== false
                && strpos(strtolower($query['query']), 'count') !== false;
        });

        $this->assertCount(1, $countQueries, 'Should execute only 1 query with GROUP BY for all companies');

        // Assert counts are correct
        $this->assertEquals(3, $count1);
        $this->assertEquals(5, $count2);
        $this->assertEquals(0, $count3);
    }

    /** @test */
    public function it_prevents_n_plus_1_when_loading_follower_counts_for_company_list()
    {
        // Arrange: 20 companies with varying follower counts
        $companies = Company::factory()->count(20)->create();
        $users = User::factory()->count(50)->create();

        // Assign followers randomly to companies
        $expectedCounts = [];
        foreach ($companies as $index => $company) {
            $followersCount = $index % 5; // 0, 1, 2, 3, 4, 0, 1, 2, 3, 4...
            $expectedCounts[$company->id] = $followersCount;

            for ($i = 0; $i < $followersCount; $i++) {
                CompanyFollower::create([
                    'user_id' => $users[($index * 5 + $i) % 50]->id,
                    'company_id' => $company->id,
                ]);
            }
        }

        // Act: Load follower counts for all companies
        DB::enableQueryLog();

        $loader = app(FollowersCountByCompanyIdBatchLoader::class);
        $counts = [];
        foreach ($companies as $company) {
            $counts[$company->id] = $loader->load($company->id);
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert: Only 1 query (not 20)
        $countQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_company_followers') !== false
                && strpos(strtolower($query['query']), 'count') !== false;
        });

        $this->assertCount(1, $countQueries, 'Should execute only 1 query with GROUP BY, not 20 (N+1 prevented)');

        // Assert all counts are correct
        foreach ($companies as $company) {
            $this->assertEquals(
                $expectedCounts[$company->id],
                $counts[$company->id],
                "Follower count for company {$company->id} should match"
            );
        }
    }
}
