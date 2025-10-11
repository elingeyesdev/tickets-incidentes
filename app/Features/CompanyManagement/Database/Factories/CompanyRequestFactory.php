<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\CompanyRequest;
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
        return [
            'request_code' => CodeGenerator::generate('business.company_requests', CodeGenerator::COMPANY_REQUEST, 'request_code'),
            'company_name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' SRL',
            'admin_email' => $this->faker->companyEmail(),
            'business_description' => $this->faker->paragraph(5),
            'website' => $this->faker->url(),
            'industry_type' => $this->faker->randomElement(['Technology', 'Finance', 'Healthcare', 'Education', 'Retail']),
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
