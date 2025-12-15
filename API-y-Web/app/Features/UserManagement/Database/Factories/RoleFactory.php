<?php

namespace App\Features\UserManagement\Database\Factories;

use App\Features\UserManagement\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Role Factory
 *
 * Factory para crear roles fake en tests.
 * NOTA: Los roles del sistema (USER, AGENT, etc.) se crean vía migración.
 * Esta factory es útil para testing de roles custom.
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * El modelo asociado a la factory
     */
    protected $model = Role::class;

    /**
     * Define el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = strtoupper(fake()->unique()->word());

        return [
            'name' => $name,
            'display_name' => fake()->jobTitle(),
            'description' => fake()->sentence(),
            'permissions' => ['tickets.view', 'tickets.create'],
            'requires_company' => fake()->boolean(50),
            'default_dashboard' => '/dashboard',
            'priority' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Rol que requiere empresa
     */
    public function requiresCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_company' => true,
        ]);
    }

    /**
     * Rol global (no requiere empresa)
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_company' => false,
        ]);
    }

    /**
     * Rol con permisos completos
     */
    public function fullPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => ['*'],
            'priority' => 100,
        ]);
    }

    /**
     * Rol sin permisos
     */
    public function noPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => [],
        ]);
    }
}