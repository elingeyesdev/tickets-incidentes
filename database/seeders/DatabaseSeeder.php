<?php

namespace Database\Seeders;

use App\Features\CompanyManagement\Database\Seeders\CompanyIndustrySeeder;
use App\Features\CompanyManagement\Database\Seeders\RealBolivianCompaniesSeeder;
use App\Features\UserManagement\Database\Seeders\RolesSeeder;
use App\Features\UserManagement\Database\Seeders\DefaultUserSeeder;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder - Master seeder for testing environment
 *
 * IMPORTANT: This seeder is executed automatically in testing when using:
 * - RefreshDatabase trait with $seed = true
 * - php artisan db:seed in testing environment
 *
 * Purpose:
 * - Seed essential data required for all tests (roles, system config, etc.)
 * - DO NOT seed feature-specific data here (use feature seeders instead)
 *
 * Why is this needed?
 * - The roles are inserted in the migration (create_roles_table)
 * - BUT RefreshDatabase drops all tables before each test
 * - Some tests might run before migrations complete, causing FK violations
 * - This seeder ensures roles exist AFTER migrations run
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles ALWAYS (required for FK constraints in user_roles table)
        // This ensures auth.roles has the 4 system roles: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
        $this->call(RolesSeeder::class);

        // Seed company industries (required for CompanyManagement feature tests)
        // This ensures business.company_industries has all industry options
        $this->call(CompanyIndustrySeeder::class);

        // Seed default platform admin user (for development/testing)
        $this->call(DefaultUserSeeder::class);

        // ⚠️ IMPORTANT: RealBolivianCompaniesSeeder is DISABLED in DatabaseSeeder
        //
        // REASON: This seeder is executed automatically by RefreshDatabase trait in tests.
        // It creates 5 companies with demo data, which breaks test assertions that expect
        // specific company counts (tests expect 3, but get 8 after seeding).
        //
        // Tests that need production-like companies should:
        // 1. Create their own test companies using factories
        // 2. NOT rely on DatabaseSeeder to create them
        //
        // TO RUN MANUALLY IN PRODUCTION/DEVELOPMENT:
        // php artisan db:seed --class=App\\Features\\CompanyManagement\\Database\\Seeders\\RealBolivianCompaniesSeeder
        //
        // DO NOT uncomment unless you want to break all company-related tests!
        // $this->call(RealBolivianCompaniesSeeder::class);

        // Future: Add other essential seeders here
        // Example:
        // $this->call(SystemConfigSeeder::class);
        // $this->call(DefaultPermissionsSeeder::class);
    }
}
