<?php

namespace Tests\Traits;

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
 * Simply use this trait instead of RefreshDatabase in your test class:
 * ```php
 * use RefreshDatabaseWithoutTransactions;
 * ```
 *
 * The setUp() and tearDown() hooks are automatically called.
 *
 * @package Tests\Traits
 */
trait RefreshDatabaseWithoutTransactions
{
    /**
     * Setup the test environment before each test
     *
     * Automatically called by PHPUnit before each test method.
     * Resets the database using migrate:fresh --seed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations fresh with seeding
        // This will DROP all tables and recreate them
        // Note: This is not wrapped in a transaction, so all HTTP requests
        // in this test will see the same database state
        Artisan::call('migrate:fresh', ['--seed' => true, '--quiet' => true]);
    }

    /**
     * Clean up after the test
     *
     * Automatically called by PHPUnit after each test method.
     * Since we're not using transactions, the next test's setUp()
     * will run migrate:fresh again, so cleanup is automatic.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Database will be reset in the next test's setUp()
        parent::tearDown();
    }
}
