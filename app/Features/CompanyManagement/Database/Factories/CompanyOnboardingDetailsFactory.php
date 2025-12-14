<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyOnboardingDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para CompanyOnboardingDetails
 * 
 * Genera datos de proceso de solicitud/onboarding para pruebas.
 */
class CompanyOnboardingDetailsFactory extends Factory
{
    protected $model = CompanyOnboardingDetails::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'request_code' => 'REQ-' . date('Y') . '-' . str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'request_message' => $this->faker->paragraph(3),
            'estimated_users' => $this->faker->numberBetween(5, 500),
            'submitter_email' => $this->faker->companyEmail(),
        ];
    }

    /**
     * Estado: Con revisión completada (aprobado).
     */
    public function reviewed(): static
    {
        return $this->state(fn(array $attributes) => [
            'reviewed_by' => \App\Features\UserManagement\Models\User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Estado: Rechazado.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'reviewed_by' => \App\Features\UserManagement\Models\User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->sentence(10),
        ]);
    }
}
