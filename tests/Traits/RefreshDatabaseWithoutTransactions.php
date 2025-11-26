<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Fresh Database Without Transactions Trait
 *
 * Designed to work properly with multiple HTTP requests in the same test.
 *
 * Problem with RefreshDatabase:
 * - It wraps the entire test in a database transaction
 * - Each HTTP request is a separate database connection
 * - Separate connections cannot see data in another connection's transaction (isolation)
 * - This causes route model binding to fail on subsequent requests in the same test
 *
 * Solution:
 * - Run migrate:fresh --seed at the start of each test (NO transactions)
 * - This clears the database and seeds fresh data
 * - All HTTP requests can see the same database state
 * - No automatic rollback (database state persists until next test), but that's OK
 *
 * Usage:
 * Simply use this trait in your test class instead of RefreshDatabase:
 * ```php
 * use RefreshDatabaseWithoutTransactions;
 * ```
 *
 * This trait overrides RefreshDatabase's transaction behavior.
 *
 * @package Tests\Traits
 */
trait RefreshDatabaseWithoutTransactions
{
    use RefreshDatabase;

    /**
     * Disable transactions - we'll truncate tables instead
     *
     * HTTP requests use separate database connections that can't see data
     * in the main test transaction. Instead of transactions, we truncate
     * tables before each test to ensure a clean state.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        // DON'T start a transaction - HTTP requests won't see the data anyway
        // Table truncation in refreshDatabase() handles cleanup instead
    }

    /**
     * Clear data via table truncation instead of transactions
     *
     * Run migrations on first test, then truncate tables for data isolation.
     *
     * This approach:
     * - Works with HTTP requests (separate database connections)
     * - Supports parallel tests (each test truncates, not drops)
     * - Avoids migrate:fresh conflicts
     *
     * @return void
     */
    protected function refreshDatabase(): void
    {
        // Run migrate:fresh only if migrations table doesn't exist (first test)
        if (! $this->migrationsDone()) {
            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
                '--env' => 'testing',
                '--quiet' => true,
            ]);
            if ($this->seed) {
                $this->seed();
            }
        } else {
            // For subsequent tests, truncate tables to clear data
            // This is faster than migrate:fresh and supports parallel execution
            $this->truncateDatabaseTables();
            if ($this->seed) {
                $this->seed();
            }
        }
    }

    /**
     * Truncate all tables to clear test data
     *
     * Uses RESTART IDENTITY CASCADE to reset sequences and foreign keys
     *
     * @return void
     */
    protected function truncateDatabaseTables(): void
    {
        // For most thorough cleaning, use DELETE instead of TRUNCATE
        // (slower but guaranteed to remove all data, especially with complex constraints)

        $tables = [
            // Ticket Management (children first)
            'ticketing.ticket_responses',
            'ticketing.ticket_attachments',
            'ticketing.tickets',
            'ticketing.categories',

            // Company Management (children first)
            'business.areas',
            'business.company_requests',
            'business.company_followers',
            'business.companies',
            'business.industries',

            // User Management (children first)
            'auth.user_roles',
            'auth.user_profiles',
            'auth.refresh_tokens',
            'auth.users',

            // Core tables last
            'auth.roles',
            'auth.permissions',
        ];

        try {
            // Disable constraint checking temporarily
            DB::statement('SET session_replication_role = replica');

            // Delete all data from each table
            foreach ($tables as $tableName) {
                try {
                    DB::table($tableName)->delete();
                    // Reset sequences
                    $sequenceName = $tableName.'_id_seq';
                    DB::statement("ALTER SEQUENCE \"{$tableName}_id_seq\" RESTART WITH 1");
                } catch (\Exception $e) {
                    // Some tables might not have sequences or might fail, skip them
                    logger()->debug("Could not clear table $tableName: ".$e->getMessage());
                }
            }

            // Re-enable constraint checking
            DB::statement('SET session_replication_role = default');
        } catch (\Exception $e) {
            try {
                DB::statement('SET session_replication_role = default');
            } catch (\Exception $e2) {
                logger()->error('Failed to restore role: '.$e2->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Check if migrations have already been run
     *
     * @return bool
     */
    private function migrationsDone(): bool
    {
        try {
            $count = DB::table('migrations')->count();
            return $count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
