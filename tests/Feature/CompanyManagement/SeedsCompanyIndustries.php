<?php

namespace Tests\Feature\CompanyManagement;

use App\Features\CompanyManagement\Database\Seeders\CompanyIndustrySeeder;

/**
 * Trait para tests que necesitan el catálogo de industrias.
 *
 * Ejecuta automáticamente el CompanyIndustrySeeder antes de cada test.
 */
trait SeedsCompanyIndustries
{
    /**
     * Ejecutar seeder de industrias antes de cada test.
     */
    protected function seedCompanyIndustries(): void
    {
        $this->seed(CompanyIndustrySeeder::class);
    }

    /**
     * Hook into setUp to automatically seed industries.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCompanyIndustries();
    }
}
