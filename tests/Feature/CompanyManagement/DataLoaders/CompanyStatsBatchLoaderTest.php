<?php

namespace Tests\Feature\CompanyManagement\DataLoaders;

use App\Features\CompanyManagement\GraphQL\DataLoaders\CompanyStatsBatchLoader;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyStatsBatchLoaderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_loads_stats_for_a_single_company()
    {
        // Arrange
        $company = Company::factory()->create();
        $users = User::factory()->count(10)->create();

        // Add 3 active agents
        for ($i = 0; $i < 3; $i++) {
            UserRole::create([
                'user_id' => $users[$i]->id,
                'company_id' => $company->id,
                'role_code' => 'AGENT',
                'is_active' => true,
                'assigned_by_user_id' => $users[0]->id,
            ]);
        }

        // Add 2 active company_admins (should not be counted as agents)
        for ($i = 3; $i < 5; $i++) {
            UserRole::create([
                'user_id' => $users[$i]->id,
                'company_id' => $company->id,
                'role_code' => 'COMPANY_ADMIN',
                'is_active' => true,
                'assigned_by_user_id' => $users[0]->id,
            ]);
        }

        // Add 1 inactive agent (should not be counted)
        UserRole::create([
            'user_id' => $users[5]->id,
            'company_id' => $company->id,
            'role_code' => 'AGENT',
            'is_active' => false,
            'assigned_by_user_id' => $users[0]->id,
        ]);

        // Act
        $loader = app(CompanyStatsBatchLoader::class);
        $deferred = $loader->load($company->id);

        // Resolve the Deferred value
        $promise = $deferred->then(fn($v) => $v);
        SyncPromise::runQueue();
        $stats = $promise->result;

        // Assert
        $this->assertEquals(3, $stats['active_agents_count']);
        $this->assertEquals(5, $stats['total_users_count']); // 3 agents + 2 admins (only active)
    }

    /** @test */
    public function it_returns_zero_for_company_with_no_users()
    {
        // Arrange
        $company = Company::factory()->create();

        // Act
        $loader = app(CompanyStatsBatchLoader::class);
        $deferred = $loader->load($company->id);

        // Resolve the Deferred value
        $promise = $deferred->then(fn($v) => $v);
        SyncPromise::runQueue();
        $stats = $promise->result;

        // Assert
        $this->assertEquals(0, $stats['active_agents_count']);
        $this->assertEquals(0, $stats['total_users_count']);
    }

    /** @test */
    public function it_batches_multiple_companies_in_single_queries()
    {
        // Arrange
        $companies = Company::factory()->count(3)->create();
        $users = User::factory()->count(20)->create();

        // Company 1: 2 agents, 1 admin = 3 total users
        UserRole::create(['user_id' => $users[0]->id, 'company_id' => $companies[0]->id, 'role_code' => 'AGENT', 'is_active' => true, 'assigned_by_user_id' => $users[0]->id]);
        UserRole::create(['user_id' => $users[1]->id, 'company_id' => $companies[0]->id, 'role_code' => 'AGENT', 'is_active' => true, 'assigned_by_user_id' => $users[0]->id]);
        UserRole::create(['user_id' => $users[2]->id, 'company_id' => $companies[0]->id, 'role_code' => 'COMPANY_ADMIN', 'is_active' => true, 'assigned_by_user_id' => $users[0]->id]);

        // Company 2: 4 agents, 0 admins = 4 total users
        for ($i = 3; $i < 7; $i++) {
            UserRole::create(['user_id' => $users[$i]->id, 'company_id' => $companies[1]->id, 'role_code' => 'AGENT', 'is_active' => true, 'assigned_by_user_id' => $users[0]->id]);
        }

        // Company 3: 0 agents, 0 users

        // Act
        // Enable query log BEFORE creating loader to capture all queries
        DB::enableQueryLog();

        $loader = app(CompanyStatsBatchLoader::class);

        $deferred1 = $loader->load($companies[0]->id);
        $deferred2 = $loader->load($companies[1]->id);
        $deferred3 = $loader->load($companies[2]->id);

        // Resolve all deferred values
        $promise1 = $deferred1->then(fn($v) => $v);
        $promise2 = $deferred2->then(fn($v) => $v);
        $promise3 = $deferred3->then(fn($v) => $v);
        SyncPromise::runQueue();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $stats1 = $promise1->result;
        $stats2 = $promise2->result;
        $stats3 = $promise3->result;

        // Assert - Should be 2 queries (1 for agents, 1 for total users) with GROUP BY
        $roleQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_roles') !== false;
        });

        $this->assertCount(2, $roleQueries, 'Should execute only 2 queries (agents + users) with GROUP BY for all companies');

        // Assert stats are correct
        $this->assertEquals(2, $stats1['active_agents_count']);
        $this->assertEquals(3, $stats1['total_users_count']);

        $this->assertEquals(4, $stats2['active_agents_count']);
        $this->assertEquals(4, $stats2['total_users_count']);

        $this->assertEquals(0, $stats3['active_agents_count']);
        $this->assertEquals(0, $stats3['total_users_count']);
    }

    /** @test */
    public function it_prevents_n_plus_1_when_loading_stats_for_company_list()
    {
        // Arrange: 15 companies with varying stats
        $companies = Company::factory()->count(15)->create();
        $users = User::factory()->count(100)->create();

        $expectedStats = [];
        $userIndex = 0;

        foreach ($companies as $index => $company) {
            $agentsCount = ($index % 5) + 1; // 1, 2, 3, 4, 5, 1, 2...
            $adminsCount = ($index % 3); // 0, 1, 2, 0, 1, 2...

            // Create agents
            for ($i = 0; $i < $agentsCount; $i++) {
                UserRole::create([
                    'user_id' => $users[$userIndex++]->id,
                    'company_id' => $company->id,
                    'role_code' => 'AGENT',
                    'is_active' => true,
                    'assigned_by_user_id' => $users[0]->id,
                ]);
            }

            // Create admins
            for ($i = 0; $i < $adminsCount; $i++) {
                UserRole::create([
                    'user_id' => $users[$userIndex++]->id,
                    'company_id' => $company->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'is_active' => true,
                    'assigned_by_user_id' => $users[0]->id,
                ]);
            }

            $expectedStats[$company->id] = [
                'active_agents_count' => $agentsCount,
                'total_users_count' => $agentsCount + $adminsCount,
            ];
        }

        // Act: Load stats for all companies
        // Enable query log BEFORE creating loader to capture all queries
        DB::enableQueryLog();

        $loader = app(CompanyStatsBatchLoader::class);
        $stats = [];
        foreach ($companies as $company) {
            $stats[$company->id] = $loader->load($company->id);
        }

        // Resolve all deferred values
        $promises = [];
        foreach ($companies as $company) {
            $promises[$company->id] = $stats[$company->id]->then(fn($v) => $v);
        }
        SyncPromise::runQueue();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $resolvedStats = [];
        foreach ($companies as $company) {
            $resolvedStats[$company->id] = $promises[$company->id]->result;
        }

        // Assert: Only 2 queries (not 30 = 15 companies * 2 queries each)
        $roleQueries = collect($queries)->filter(function($query) {
            return strpos($query['query'], 'user_roles') !== false;
        });

        $this->assertCount(2, $roleQueries, 'Should execute only 2 queries (agents + users), not 30 (N+1 prevented)');

        // Assert all stats are correct
        foreach ($companies as $company) {
            $this->assertEquals(
                $expectedStats[$company->id]['active_agents_count'],
                $resolvedStats[$company->id]['active_agents_count'],
                "Active agents count for company {$company->id} should match"
            );
            $this->assertEquals(
                $expectedStats[$company->id]['total_users_count'],
                $resolvedStats[$company->id]['total_users_count'],
                "Total users count for company {$company->id} should match"
            );
        }
    }
}
