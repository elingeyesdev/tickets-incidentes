<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyRequestFactory extends Factory
{
    protected $model = CompanyRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Usar faker unique para evitar condiciones de carrera en pruebas paralelas
        $uniqueNumber = fake()->unique()->numberBetween(1, 99999);
        $year = now()->year;
        $requestCode = CodeGenerator::format(CodeGenerator::COMPANY_REQUEST, $year, $uniqueNumber);

        return [
            'request_code' => $requestCode,
            'company_name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' SRL',
            'admin_email' => $this->faker->companyEmail(),
            'company_description' => $this->faker->realText(250),
            'request_message' => $this->faker->sentence(15),
            'website' => $this->faker->url(),
            'industry_id' => fn() => CompanyIndustry::inRandomOrder()->first()?->id
                ?? CompanyIndustry::factory()->create()->id,
            'estimated_users' => $this->faker->numberBetween(10, 500),
            'contact_address' => $this->faker->streetAddress(),
            'contact_city' => $this->faker->city(),
            'contact_country' => 'Bolivia',
            'contact_postal_code' => $this->faker->postcode(),
            'tax_id' => $this->faker->numerify('#########'),
            'status' => 'pending',
        ];
    }

    /**
     * Indicate that the request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->paragraph(),
        ]);
    }
}
