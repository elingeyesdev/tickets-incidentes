<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\UserManagement\Database\Seeders\RolesSeeder;
use App\Features\CompanyManagement\Database\Seeders\CompanyIndustrySeeder;

class TestingDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for TESTING environment.
     *
     * This seeder is lighter than DatabaseSeeder and only includes
     * structural data required for tests to run (Roles, Permissions, Catalogs).
     * It avoids creating heavy demo data (Tickets, Articles, etc.) which should
     * be created via Factories within each test.
     */
    public function run(): void
    {
        // 0. Spatie Permission Roles (for web.php Blade integration AND API authorization)
        // Must run BEFORE user seeders to allow role assignment
        $this->call(SpatieRolesSeeder::class);

        // 1. Core System Data
        $this->call(RolesSeeder::class);
        $this->call(CompanyIndustrySeeder::class);

        // NO demo users, NO companies, NO tickets, NO articles
    }
}
