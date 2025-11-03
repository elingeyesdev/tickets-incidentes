<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

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
     * Disable the transaction-based refresh
     *
     * This overrides RefreshDatabase to NOT use transactions.
     * Instead, we use migrate:fresh which clears and rebuilds the database
     * without transaction wrapping.
     *
     * @return bool Always returns false (no transactions)
     */
    public function beginDatabaseTransaction()
    {
        // Don't start a database transaction
        // This allows multiple HTTP requests in the same test to see each other's data
        return false;
    }

    /**
     * Override refresh to not use transactions
     *
     * Called by RefreshDatabase before each test
     *
     * @return void
     */
    protected function refreshDatabase(): void
    {
        // Run migrate:fresh to clear and rebuild database
        // This is NOT wrapped in a transaction
        Artisan::call('migrate:fresh', ['--seed' => true, '--quiet' => true]);
    }

    /**
     * Disable transaction rollback
     *
     * Since we're not using transactions, there's nothing to rollback
     *
     * @return void
     */
    protected function rollbackTransaction()
    {
        // Nothing to rollback - we're using migrate:fresh, not transactions
    }
}
