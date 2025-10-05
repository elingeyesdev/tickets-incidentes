<?php

namespace App\Features\UserManagement\Database\Factories;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * UserProfile Factory
 *
 * Factory para crear perfiles de usuario fake en tests.
 *
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * El modelo asociado a la factory
     */
    protected $model = UserProfile::class;

    /**
     * Define el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => User::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => "{$firstName} {$lastName}",
            'phone_number' => fake()->optional(0.7)->phoneNumber(),
            'avatar_url' => fake()->optional(0.5)->imageUrl(200, 200, 'people'),
            'theme' => fake()->randomElement(['light', 'dark']),
            'language' => fake()->randomElement(['es', 'en']),
            'timezone' => fake()->randomElement([
                'America/La_Paz',
                'America/New_York',
                'America/Los_Angeles',
                'Europe/Madrid',
            ]),
            'push_web_notifications' => fake()->boolean(80),
            'notifications_tickets' => fake()->boolean(70),
            'last_activity_at' => fake()->optional(0.8)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Perfil con tema oscuro
     */
    public function darkTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => 'dark',
        ]);
    }

    /**
     * Perfil con tema claro
     */
    public function lightTheme(): static
    {
        return $this->state(fn (array $attributes) => [
            'theme' => 'light',
        ]);
    }

    /**
     * Perfil con idioma inglés
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'en',
            'timezone' => 'America/New_York',
        ]);
    }

    /**
     * Perfil con idioma español
     */
    public function spanish(): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);
    }

    /**
     * Perfil sin notificaciones
     */
    public function noNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'push_web_notifications' => false,
            'notifications_tickets' => false,
        ]);
    }

    /**
     * Perfil con todas las notificaciones
     */
    public function allNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'push_web_notifications' => true,
            'notifications_tickets' => true,
        ]);
    }

    /**
     * Perfil completo (con todos los campos opcionales llenos)
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => fake()->phoneNumber(),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
            'last_activity_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    /**
     * Perfil incompleto (sin campos opcionales)
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_number' => null,
            'avatar_url' => null,
            'last_activity_at' => null,
        ]);
    }
}