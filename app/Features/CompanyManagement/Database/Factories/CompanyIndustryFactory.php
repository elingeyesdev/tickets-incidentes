<?php

namespace App\Features\CompanyManagement\Database\Factories;

use App\Features\CompanyManagement\Models\CompanyIndustry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * CompanyIndustry Factory
 *
 * Generates test data for CompanyIndustry models.
 * Note: In production, industries are seeded with predefined data.
 */
class CompanyIndustryFactory extends Factory
{
    protected $model = CompanyIndustry::class;

    /**
     * Lista de industrias de ejemplo para testing
     */
    private static array $industries = [
        ['code' => 'technology', 'name' => 'Tecnología'],
        ['code' => 'healthcare', 'name' => 'Salud'],
        ['code' => 'education', 'name' => 'Educación'],
        ['code' => 'finance', 'name' => 'Finanzas'],
        ['code' => 'retail', 'name' => 'Retail'],
        ['code' => 'manufacturing', 'name' => 'Manufactura'],
        ['code' => 'construction', 'name' => 'Construcción'],
        ['code' => 'real_estate', 'name' => 'Inmobiliaria'],
        ['code' => 'hospitality', 'name' => 'Hotelería'],
        ['code' => 'transportation', 'name' => 'Transporte'],
    ];

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        // Seleccionar una industria aleatoria de la lista
        $industry = $this->faker->randomElement(self::$industries);

        return [
            'code' => $industry['code'] . '_' . $this->faker->unique()->numberBetween(1, 9999),
            'name' => $industry['name'] . ' ' . $this->faker->words(2, true),
            'description' => $this->faker->sentence(10),
        ];
    }

    /**
     * Estado: Industria de tecnología
     */
    public function technology(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'technology',
            'name' => 'Tecnología',
            'description' => 'Empresas del sector tecnológico y desarrollo de software',
        ]);
    }

    /**
     * Estado: Industria de salud
     */
    public function healthcare(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'healthcare',
            'name' => 'Salud',
            'description' => 'Instituciones de salud, clínicas y hospitales',
        ]);
    }

    /**
     * Estado: Industria de educación
     */
    public function education(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'education',
            'name' => 'Educación',
            'description' => 'Instituciones educativas y centros de formación',
        ]);
    }

    /**
     * Estado: Industria de finanzas
     */
    public function finance(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'finance',
            'name' => 'Finanzas',
            'description' => 'Instituciones financieras, bancos y cooperativas',
        ]);
    }

    /**
     * Estado: Industria de retail
     */
    public function retail(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'retail',
            'name' => 'Retail',
            'description' => 'Comercio minorista y tiendas',
        ]);
    }
}
