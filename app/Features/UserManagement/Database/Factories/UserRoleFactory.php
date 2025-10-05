<?php

namespace App\Features\UserManagement\Database\Factories;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Features\UserManagement\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * UserRole Factory
 *
 * Factory para crear asignaciones de roles fake en tests.
 *
 * @extends Factory<UserRole>
 */
class UserRoleFactory extends Factory
{
    /**
     * El modelo asociado a la factory
     */
    protected $model = UserRole::class;

    /**
     * Define el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => Role::where('name', 'USER')->first()->id ?? Role::factory(),
            'company_id' => null, // Por defecto rol global (USER)
            'is_active' => true,
            'assigned_at' => now()->subDays(rand(1, 365)),
            'revoked_at' => null,
            'assigned_by_id' => null,
            'revoked_by_id' => null,
        ];
    }

    /**
     * Rol activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'revoked_at' => null,
            'revoked_by_id' => null,
        ]);
    }

    /**
     * Rol revocado
     */
    public function revoked(?string $revokedById = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'revoked_at' => now()->subDays(rand(1, 30)),
            'revoked_by_id' => $revokedById,
        ]);
    }

    /**
     * Rol con contexto de empresa
     */
    public function forCompany(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }

    /**
     * Rol global (sin empresa)
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
        ]);
    }

    /**
     * Rol de USER
     */
    public function userRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'USER')->first()->id,
            'company_id' => null,
        ]);
    }

    /**
     * Rol de AGENT
     */
    public function agentRole(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'AGENT')->first()->id,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Rol de COMPANY_ADMIN
     */
    public function companyAdminRole(string $companyId): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'COMPANY_ADMIN')->first()->id,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Rol de PLATFORM_ADMIN
     */
    public function platformAdminRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', 'PLATFORM_ADMIN')->first()->id,
            'company_id' => null,
        ]);
    }

    /**
     * Rol asignado recientemente
     */
    public function recentlyAssigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_at' => now()->subDays(rand(1, 7)),
        ]);
    }

    /**
     * Rol asignado por un usuario específico
     */
    public function assignedBy(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_by_id' => $userId,
        ]);
    }
}