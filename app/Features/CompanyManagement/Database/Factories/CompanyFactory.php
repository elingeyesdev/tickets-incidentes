<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_code' => CodeGenerator::generate('CMP'),
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' SRL',
            'support_email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'contact_address' => $this->faker->streetAddress(),
            'contact_city' => $this->faker->city(),
            'contact_state' => $this->faker->state(),
            'contact_country' => 'Bolivia',
            'contact_postal_code' => $this->faker->postcode(),
            'tax_id' => $this->faker->numerify('#########'),
            'legal_representative' => $this->faker->name(),
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '09:00', 'close' => '17:00'],
            ],
            'timezone' => 'America/La_Paz',
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'favicon_url' => $this->faker->imageUrl(32, 32, 'business'),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'settings' => [],
            'status' => 'active',
            'admin_user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the company is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
