<?php

namespace App\Features\TicketManagement\Database\Factories;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Category
 *
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Definir el estado por defecto del modelo.
     */
    public function definition(): array
    {
        $categories = [
            'Soporte Técnico' => 'Problemas técnicos con el sistema',
            'Facturación' => 'Consultas sobre pagos y facturas',
            'Cuenta y Perfil' => 'Gestión de cuenta de usuario',
            'Reportes' => 'Consultas sobre reportes y analíticas',
            'Seguridad' => 'Temas de seguridad y privacidad',
            'General' => 'Consultas generales',
        ];

        $category = $this->faker->randomElement(array_keys($categories));

        return [
            'company_id' => Company::factory(),
            'name' => $category,
            'description' => $categories[$category],
            'is_active' => true,
        ];
    }

    /**
     * Indicar que la categoría está inactiva
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Categoría con nombre personalizado
     */
    public function withName(string $name, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'description' => $description ?? "Categoría de {$name}",
        ]);
    }

    /**
     * Categoría para una empresa específica
     */
    public function forCompany(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }
}
