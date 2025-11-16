<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        // Usar faker unique para evitar condiciones de carrera en pruebas paralelas
        $uniqueNumber = fake()->unique()->numberBetween(1, 99999);
        $year = now()->year;
        $companyCode = CodeGenerator::format(CodeGenerator::COMPANY, $year, $uniqueNumber);

        return [
            'company_code' => $companyCode,
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' SRL',
            'description' => $this->faker->optional(0.8)->realText(200),
            'support_email' => $this->faker->companyEmail(),
            'phone' => '+591' . $this->faker->numerify('########'),  // E.164 format for Bolivia
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
            'logo_url' => 'https://via.placeholder.com/200x200?text=Logo',
            'favicon_url' => 'https://via.placeholder.com/32x32?text=Favicon',
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'settings' => [],
            'status' => 'active',
            'admin_user_id' => User::factory(),
            'industry_id' => CompanyIndustry::inRandomOrder()->first()?->id
                ?? CompanyIndustry::factory()->create()->id,
        ];
    }

    /**
     * Indicar que la empresa está suspendida.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicar que la empresa pertenece a una industria específica.
     */
    public function withIndustry(string $industryCode): static
    {
        return $this->state(fn (array $attributes) => [
            'industry_id' => CompanyIndustry::where('code', $industryCode)->first()?->id
                ?? CompanyIndustry::factory()->create(['code' => $industryCode])->id,
        ]);
    }
}
