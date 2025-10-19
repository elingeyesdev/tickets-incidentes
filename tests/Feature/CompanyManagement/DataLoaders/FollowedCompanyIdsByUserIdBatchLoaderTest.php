<?php

namespace Tests\Feature\CompanyManagement\DataLoaders;

use App\Features\CompanyManagement\GraphQL\DataLoaders\FollowedCompanyIdsByUserIdBatchLoader;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyFollower;
use App\Features\UserManagement\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FollowedCompanyIdsByUserIdBatchLoaderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_loads_followed_company_ids_for_a_single_user()
    {
        // Arrange
        $user = User::factory()->create();
        $companies = Company::factory()->count(3)->create();

        // User follows 2 companies
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companies[0]->id]);
        CompanyFollower::create(['user_id' => $user->id, 'company_id' => $companies[1]->id]);

        // Act
        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
        $followedIds = $loader->load($user->id);

        // Assert
        $this->assertIsArray($followedIds);
        $this->assertCount(2, $followedIds);
        $this->assertContains($companies[0]->id, $followedIds);
        $this->assertContains($companies[1]->id, $followedIds);
        $this->assertNotContains($companies[2]->id, $followedIds);
    }

    /** @test */
    public function it_returns_empty_array_for_user_with_no_follows()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
        $followedIds = $loader->load($user->id);

        // Assert
        $this->assertIsArray($followedIds);
        $this->assertEmpty($followedIds);
    }

    /** @test */
    public function it_batches_multiple_users_in_single_query()
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $companies = Company::factory()->count(5)->create();

        // User 1 follows companies 0, 1
        CompanyFollower::create(['user_id' => $users[0]->id, 'company_id' => $companies[0]->id]);
        CompanyFollower::create(['user_id' => $users[0]->id, 'company_id' => $companies[1]->id]);

        // User 2 follows company 2
        CompanyFollower::create(['user_id' => $users[1]->id, 'company_id' => $companies[2]->id]);

        // User 3 follows nothing

        // Act - Simulate multiple loads (DataLoader pattern)
        DB::enableQueryLog();
        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);

        $deferred1 = $loader->load($users[0]->id);
        $deferred2 = $loader->load($users[1]->id);
        $deferred3 = $loader->load($users[2]->id);

        // Resolve all deferred values (triggers batch query)
        $followedIds1 = $deferred1->then(fn($v) => $v)->wait();
        $followedIds2 = $deferred2->then(fn($v) => $v)->wait();
        $followedIds3 = $deferred3->then(fn($v) => $v)->wait();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert - Should be only 1 query for all 3 users
        $followerQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_company_followers') !== false;
        });

        $this->assertCount(1, $followerQueries, 'Should execute only 1 query for all users (batch loading)');

        // Assert results are correct
        $this->assertCount(2, $followedIds1);
        $this->assertCount(1, $followedIds2);
        $this->assertEmpty($followedIds3);
    }

    /** @test */
    public function it_prevents_n_plus_1_queries_in_company_list()
    {
        // Arrange: 20 companies, 1 user following 5 of them
        $companies = Company::factory()->count(20)->create();
        $user = User::factory()->create();

        // User follows companies 0, 5, 10, 15, 19
        foreach ([0, 5, 10, 15, 19] as $index) {
            CompanyFollower::create([
                'user_id' => $user->id,
                'company_id' => $companies[$index]->id,
            ]);
        }

        // Act: Simulate GraphQL query companies(EXPLORE) checking isFollowedByMe
        DB::enableQueryLog();

        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
        $followedIds = $loader->load($user->id);

        // Simulate checking isFollowedByMe for all 20 companies
        $results = [];
        foreach ($companies as $company) {
            $results[$company->id] = in_array($company->id, $followedIds);
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert: Only 1 query to load follows (not 20)
        $followerQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_company_followers') !== false;
        });

        $this->assertCount(1, $followerQueries, 'Should execute only 1 query, not 20 (N+1 prevented)');

        // Assert results are correct
        $this->assertTrue($results[$companies[0]->id]);
        $this->assertTrue($results[$companies[5]->id]);
        $this->assertTrue($results[$companies[10]->id]);
        $this->assertFalse($results[$companies[1]->id]);
        $this->assertFalse($results[$companies[2]->id]);
    }
}
