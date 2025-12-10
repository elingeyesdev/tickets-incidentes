<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Features\UserManagement\Database\Seeders\RolesSeeder;
use App\Features\CompanyManagement\Database\Seeders\CompanyIndustrySeeder;
use App\Features\UserManagement\Database\Seeders\DefaultUserSeeder;
use App\Features\CompanyManagement\Database\Seeders\LargeBolivianCompaniesSeeder;
use App\Features\CompanyManagement\Database\Seeders\MediumBolivianCompaniesSeeder;
use App\Features\CompanyManagement\Database\Seeders\SmallBolivianCompaniesSeeder;
use App\Features\CompanyManagement\Database\Seeders\CompanyRequestApprovalSimulationSeeder;

// Articles
use App\Features\ContentManagement\Database\Seeders\PilAndinaArticlesSeeder;
use App\Features\ContentManagement\Database\Seeders\BancoFassilHelpCenterArticlesSeeder;
use App\Features\ContentManagement\Database\Seeders\YPFBHelpCenterArticlesSeeder;
use App\Features\ContentManagement\Database\Seeders\TigoHelpCenterArticlesSeeder;
use App\Features\ContentManagement\Database\Seeders\CBNHelpCenterArticlesSeeder;

// Announcements
use App\Features\ContentManagement\Database\Seeders\PilAndinaAnnouncementsSeeder;
use App\Features\ContentManagement\Database\Seeders\BancoFassilAnnouncementsSeeder;
use App\Features\ContentManagement\Database\Seeders\YPFBAnnouncementsSeeder;
use App\Features\ContentManagement\Database\Seeders\TigoAnnouncementsSeeder;
use App\Features\ContentManagement\Database\Seeders\CerveceriaBolividanaAnnouncementsSeeder;

// Tickets
use App\Features\TicketManagement\Database\Seeders\PilAndinaTicketsSeeder;
use App\Features\TicketManagement\Database\Seeders\PilAndinaAugOctTicketsSeeder;

// Test Users
use App\Features\UserManagement\Database\Seeders\MultiRoleTestUserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Spatie Permission Roles (for web.php Blade integration)
        // Must run BEFORE user seeders to allow role assignment
        $this->call(SpatieRolesSeeder::class);

        // 1. Core System Data
        $this->call(RolesSeeder::class);
        $this->call(CompanyIndustrySeeder::class);
        $this->call(DefaultUserSeeder::class);

        // 2. Companies (incluye logos integrados)
        $this->call(LargeBolivianCompaniesSeeder::class);
        $this->call(MediumBolivianCompaniesSeeder::class);
        $this->call(SmallBolivianCompaniesSeeder::class);

        // 2.5. Company Request Approval Simulation (crea solicitudes aprobadas/pendientes/rechazadas)
        $this->call(CompanyRequestApprovalSimulationSeeder::class);

        // 3. Articles (One by one)
        // $this->call(PilAndinaArticlesSeeder::class); // Comentado - archivo no existe
        $this->call(BancoFassilHelpCenterArticlesSeeder::class);
        $this->call(YPFBHelpCenterArticlesSeeder::class);
        $this->call(TigoHelpCenterArticlesSeeder::class);
        $this->call(CBNHelpCenterArticlesSeeder::class);

        // 4. Announcements (One by one)
        $this->call(PilAndinaAnnouncementsSeeder::class);
        $this->call(BancoFassilAnnouncementsSeeder::class);
        $this->call(YPFBAnnouncementsSeeder::class);
        $this->call(TigoAnnouncementsSeeder::class);
        $this->call(CerveceriaBolividanaAnnouncementsSeeder::class);

        // 5. Tickets
        $this->call(PilAndinaTicketsSeeder::class);
        $this->call(PilAndinaAugOctTicketsSeeder::class); // Históricos Ago-Oct 2025

        // 6. Test Users (Multi-role user for testing active_role system)
        $this->call(MultiRoleTestUserSeeder::class);
    }
}
