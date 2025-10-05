<?php

namespace App\Features\Authentication\Database\Factories;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * RefreshToken Factory
 *
 * Factory para crear refresh tokens fake en tests.
 *
 * @extends Factory<RefreshToken>
 */
class RefreshTokenFactory extends Factory
{
    /**
     * El modelo asociado a la factory
     */
    protected $model = RefreshToken::class;

    /**
     * Define el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'device_name' => fake()->randomElement([
                'Chrome on Windows',
                'Safari on macOS',
                'Firefox on Linux',
                'Safari on iPhone',
                'Chrome on Android',
                'Edge on Windows',
            ]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'expires_at' => now()->addDays(30),
            'last_used_at' => now(),
            'is_revoked' => false,
            'revoked_at' => null,
            'revoked_by_id' => null,
        ];
    }

    /**
     * Token expirado
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Token revocado
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_revoked' => true,
            'revoked_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    /**
     * Token recién creado (nunca usado)
     */
    public function unused(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }

    /**
     * Token usado recientemente
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now()->subMinutes(rand(1, 15)),
        ]);
    }

    /**
     * Token para un usuario específico
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Token para dispositivo móvil
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_name' => fake()->randomElement([
                'Safari on iPhone',
                'Chrome on Android',
                'Safari on iPad',
            ]),
            'user_agent' => fake()->randomElement([
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
                'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/114.0.0.0',
                'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
            ]),
        ]);
    }

    /**
     * Token para dispositivo de escritorio
     */
    public function desktop(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_name' => fake()->randomElement([
                'Chrome on Windows',
                'Safari on macOS',
                'Firefox on Linux',
                'Edge on Windows',
            ]),
            'user_agent' => fake()->randomElement([
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0.0.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_4) AppleWebKit/605.1.15 Safari/605.1.15',
                'Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/114.0',
            ]),
        ]);
    }

    /**
     * Token que expira pronto (en menos de 24 horas)
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(rand(1, 23)),
        ]);
    }

    /**
     * Token con una IP específica
     */
    public function withIp(string $ip): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ip,
        ]);
    }
}