<?php

namespace App\Features\UserManagement\Database\Factories;

use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * User Factory
 *
 * Factory para crear usuarios fake en tests.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * El modelo asociado a la factory
     */
    protected $model = User::class;

    /**
     * Define el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate unique user_code for testing using faker sequence
        // Format: USR-YYYY-XXXXX where XXXXX is unique in test context
        $uniqueNumber = fake()->unique()->numberBetween(1, 99999);
        $year = now()->year;
        $userCode = CodeGenerator::format(CodeGenerator::USER, $year, $uniqueNumber);

        return [
            'user_code' => $userCode,
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make('password'), // Default password for testing
            'email_verified' => true,
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'onboarding_completed_at' => now()->subDays(rand(1, 30)),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => fake()->optional()->ipv4(),
            'terms_accepted' => true,
            'terms_accepted_at' => now()->subDays(rand(1, 365)),
            'terms_version' => 'v2.1',
        ];
    }

    /**
     * Usuario no verificado
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified' => false,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Usuario suspendido
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    /**
     * Usuario eliminado (soft delete)
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::DELETED,
            'deleted_at' => now(),
        ]);
    }

    /**
     * Usuario con términos no aceptados
     */
    public function termsNotAccepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'terms_accepted' => false,
            'terms_accepted_at' => null,
            'terms_version' => null,
        ]);
    }

    /**
     * Usuario con autenticación OAuth
     */
    public function oauth(string $provider = 'google'): static
    {
        return $this->state(fn (array $attributes) => [
            'auth_provider' => $provider,
            'password_hash' => null, // OAuth users don't have password
        ]);
    }

    /**
     * Usuario activo recientemente
     */
    public function recentlyActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    /**
     * Usuario con perfil (crea el perfil automáticamente)
     */
    public function withProfile(array $profileOverrides = []): static
    {
        return $this->afterCreating(function (User $user) use ($profileOverrides) {
            $user->profile()->create(array_merge([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone_number' => fake()->optional()->phoneNumber(),
                'avatar_url' => fake()->optional()->imageUrl(),
                'theme' => fake()->randomElement(['light', 'dark']),
                'language' => fake()->randomElement(['es', 'en']),
                'timezone' => 'America/La_Paz',
                'push_web_notifications' => fake()->boolean(80),
                'notifications_tickets' => fake()->boolean(70),
            ], $profileOverrides));
        });
    }

    /**
     * Usuario con rol asignado
     *
     * @param string $roleCode Código del rol (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
     * @param string|null $companyId ID de la empresa (requerido para AGENT y COMPANY_ADMIN)
     */
    public function withRole(string $roleCode, ?string $companyId = null): static
    {
        return $this->afterCreating(function (User $user) use ($roleCode, $companyId) {
            $user->assignRole($roleCode, $companyId);
        });
    }

    /**
     * Usuario verificado (email verificado)
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Usuario con onboarding pendiente
     *
     * IMPORTANTE: Email verification NO es prerequisito para onboarding.
     * Este estado simula un usuario que aún no completó su perfil y preferencias.
     */
    public function onboardingPending(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_completed' => false,
            'onboarding_completed_at' => null,
        ]);
    }

    /**
     * Usuario con onboarding completado
     *
     * Usuario que ya completó profile + preferences
     */
    public function onboardingCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarding_completed' => true,
            'onboarding_completed_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}