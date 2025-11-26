<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Area
 *
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->optional(0.7)->sentence(10),
            'is_active' => true,
        ];
    }

    /**
     * Área inactiva
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Área para una empresa específica
     */
    public function forCompany(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }
}
