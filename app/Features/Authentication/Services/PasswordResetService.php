<?php

namespace App\Features\Authentication\Services;

use App\Features\Authentication\Events\PasswordResetCompleted;
use App\Features\Authentication\Events\PasswordResetRequested;
use App\Features\UserManagement\Models\User;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PasswordResetService
 *
 * Servicio para gestión de reset de contraseñas.
 *
 * Responsabilidades:
 * - Solicitar reset de contraseña (siempre retorna true por seguridad)
 * - Validar tokens de reset
 * - Confirmar reset de contraseña
 * - Gestionar intentos de reset
 */
class PasswordResetService
{
    public function __construct(
        private TokenService $tokenService
    ) {
    }

    /**
     * Solicitar reset de contraseña
     *
     * IMPORTANTE: Siempre retorna true por seguridad,
     * incluso si el email no existe en el sistema.
     *
     * @param string $email
     * @return bool Siempre true
     */
    public function requestReset(string $email): bool
    {
        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        // Si el usuario no existe, retornar true pero no hacer nada
        // (NO revelar si el email existe o no)
        if (!$user) {
            return true;
        }

        // Si el usuario está inactivo, retornar true pero no enviar email
        if (!$user->isActive()) {
            return true;
        }

        // Generar token de reset
        $resetToken = $this->generateResetToken($user);

        // Disparar evento (enviará email)
        event(new PasswordResetRequested($user, $resetToken));

        return true;
    }

    /**
     * Generar token de reset y guardarlo en cache
     *
     * @param User $user
     * @return string Token de reset
     */
    public function generateResetToken(User $user): string
    {
        // Generar token aleatorio de 32 caracteres
        $token = 'reset_' . Str::random(32);

        // Guardar en cache con TTL de 1 hora
        $key = $this->getResetTokenKey($token);

        Cache::put($key, [
            'user_id' => $user->id,
            'email' => $user->email,
            'expires_at' => now()->addHour()->timestamp,
            'attempts_remaining' => 3,
        ], now()->addHour());

        return $token;
    }

    /**
     * Validar token de reset
     *
     * @param string $token
     * @return array ['is_valid' => bool, 'email' => string|null, 'expires_at' => int|null, 'attempts_remaining' => int]
     */
    public function validateResetToken(string $token): array
    {
        $key = $this->getResetTokenKey($token);
        $data = Cache::get($key);

        if (!$data) {
            return [
                'is_valid' => false,
                'email' => null,
                'expires_at' => null,
                'attempts_remaining' => 0,
            ];
        }

        // Verificar expiración
        if ($data['expires_at'] < now()->timestamp) {
            Cache::forget($key);
            return [
                'is_valid' => false,
                'email' => null,
                'expires_at' => null,
                'attempts_remaining' => 0,
            ];
        }

        // Verificar intentos restantes
        if ($data['attempts_remaining'] <= 0) {
            return [
                'is_valid' => false,
                'email' => $this->maskEmail($data['email']),
                'expires_at' => $data['expires_at'],
                'attempts_remaining' => 0,
            ];
        }

        return [
            'is_valid' => true,
            'email' => $this->maskEmail($data['email']),
            'expires_at' => $data['expires_at'],
            'attempts_remaining' => $data['attempts_remaining'],
        ];
    }

    /**
     * Confirmar reset de contraseña
     *
     * @param string $token
     * @param string $newPassword
     * @return User
     * @throws AuthenticationException
     */
    public function confirmReset(string $token, string $newPassword): User
    {
        $key = $this->getResetTokenKey($token);
        $data = Cache::get($key);

        if (!$data) {
            throw new AuthenticationException('Invalid or expired reset token');
        }

        // Verificar expiración
        if ($data['expires_at'] < now()->timestamp) {
            Cache::forget($key);
            throw new AuthenticationException('Reset token has expired');
        }

        // Verificar intentos restantes
        if ($data['attempts_remaining'] <= 0) {
            throw new AuthenticationException('Maximum reset attempts exceeded');
        }

        // Buscar usuario
        $user = User::find($data['user_id']);

        if (!$user) {
            Cache::forget($key);
            throw new AuthenticationException('User not found');
        }

        if (!$user->isActive()) {
            Cache::forget($key);
            throw new AuthenticationException('User account is not active');
        }

        // Validar que la nueva contraseña sea diferente
        if (Hash::check($newPassword, $user->password_hash)) {
            // Decrementar intentos
            $data['attempts_remaining']--;
            Cache::put($key, $data, now()->addHour());

            throw new AuthenticationException('New password must be different from current password');
        }

        // Actualizar contraseña
        $user->password_hash = Hash::make($newPassword);
        $user->save();

        // Invalidar token
        $this->invalidateResetToken($token);

        // Revocar TODAS las sesiones del usuario (logout everywhere)
        $this->tokenService->revokeAllUserTokens($user->id, $user->id);

        // Disparar evento
        event(new PasswordResetCompleted($user));

        return $user->fresh(['profile', 'roles', 'companies']);
    }

    /**
     * Invalidar token de reset
     *
     * @param string $token
     * @return bool
     */
    public function invalidateResetToken(string $token): bool
    {
        $key = $this->getResetTokenKey($token);
        return Cache::forget($key);
    }

    /**
     * Decrementar intentos de reset
     *
     * @param string $token
     * @return int Intentos restantes
     */
    public function decrementAttempts(string $token): int
    {
        $key = $this->getResetTokenKey($token);
        $data = Cache::get($key);

        if (!$data) {
            return 0;
        }

        $data['attempts_remaining'] = max(0, $data['attempts_remaining'] - 1);

        Cache::put($key, $data, now()->addHour());

        return $data['attempts_remaining'];
    }

    /**
     * Obtener estado del reset de contraseña
     *
     * @param string $token
     * @return array
     */
    public function getResetStatus(string $token): array
    {
        return $this->validateResetToken($token);
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Generar key de cache para token de reset
     */
    private function getResetTokenKey(string $token): string
    {
        return "password_reset:{$token}";
    }

    /**
     * Enmascarar email para privacidad
     * Ejemplo: maria.garcia@empresa.com → m***a@empresa.com
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return $email;
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            return $username[0] . '***@' . $domain;
        }

        $firstChar = $username[0];
        $lastChar = $username[strlen($username) - 1];

        return $firstChar . '***' . $lastChar . '@' . $domain;
    }
}