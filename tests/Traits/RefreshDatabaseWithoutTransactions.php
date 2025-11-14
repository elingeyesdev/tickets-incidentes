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
     * CRITICAL: This method must be COMPLETELY EMPTY.
     * Laravel's RefreshDatabase::refreshTestDatabase() calls this method but
     * doesn't check the return value. If we have ANY code here (even return false),
     * Laravel will still execute its transaction logic in the parent trait.
     *
     * @return void
     */
    public function beginDatabaseTransaction()
    {
        // INTENTIONALLY EMPTY
        // Do NOT add any code here, not even "return false"
        // This prevents Laravel from starting database transactions
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
}
