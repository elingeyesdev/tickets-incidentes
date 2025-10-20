<?php

declare(strict_types=1);

namespace Database\Factories\Features\Authentication\Models;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * RefreshToken Factory
 *
 * Factory para crear RefreshToken de prueba
 */
class RefreshTokenFactory extends Factory
{
    protected $model = RefreshToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token_hash' => hash('sha256', $this->faker->unique()->uuid()),
            'device_name' => $this->faker->randomElement([
                'Chrome on Windows',
                'Safari on macOS',
                'Firefox on Linux',
                'Edge on Windows',
                'Mobile Safari on iOS',
                'Chrome on Android',
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'expires_at' => now()->addDays(30),
            'last_used_at' => now(),
            'is_revoked' => false,
            'revoked_at' => null,
            'revoke_reason' => null,
        ];
    }

    /**
     * Indicate that the refresh token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the refresh token is revoked.
     */
    public function revoked(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoke_reason' => $reason ?? 'manual_logout',
        ]);
    }

    /**
     * Indicate that the refresh token has never been used.
     */
    public function neverUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }
}
