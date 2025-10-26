<?php

namespace App\Features\Authentication\Services;

use App\Features\Authentication\Events\PasswordResetCompleted;
use App\Features\Authentication\Events\PasswordResetRequested;
use App\Features\UserManagement\Models\User;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\RateLimitExceededException;
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
     * Rate Limiting:
     * - 1 minuto entre resends del mismo email
     * - Máximo 2 emails cada 3 horas
     *
     * @param string $email
     * @return bool Siempre true
     * @throws RateLimitExceededException
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

        // === RATE LIMITING ===
        // 1. Verificar: 1 minuto entre resends
        $lastResendKey = "password_reset_resend:{$user->id}";
        if (Cache::has($lastResendKey)) {
            throw RateLimitExceededException::custom(
                'request password reset',
                limit: 1,
                windowSeconds: 60,
                retryAfter: 60
            );
        }

        // 2. Verificar: Máximo 2 emails cada 3 horas
        $countKey = "password_reset_count_3h:{$user->id}";
        $count = Cache::get($countKey, 0);
        if ($count >= 2) {
            throw RateLimitExceededException::custom(
                'request password reset',
                limit: 2,
                windowSeconds: 10800, // 3 horas
                retryAfter: 300 // 5 minutos
            );
        }

        // === PASAR RATE LIMITING ===
        // Marcar último resend (1 minuto)
        Cache::put($lastResendKey, true, now()->addSeconds(60));

        // Incrementar contador 3 horas
        if ($count === 0) {
            Cache::put($countKey, 1, now()->addHours(3));
        } else {
            Cache::increment($countKey);
        }

        // Generar token de reset
        $resetToken = $this->generateResetToken($user);

        \Log::debug('PasswordResetService: About to dispatch PasswordResetRequested event', [
            'user_id' => $user->id,
            'token' => $resetToken,
        ]);

        // Disparar evento (enviará email)
        event(new PasswordResetRequested($user, $resetToken));

        \Log::debug('PasswordResetService: Event dispatched');

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
        // Generar token aleatorio de 32 caracteres (sin prefijo para tests)
        $token = Str::random(32);

        // Guardar en cache con TTL de 24 horas (no 1 hora)
        $key = $this->getResetTokenKey($token);

        Cache::put($key, [
            'user_id' => $user->id,
            'email' => $user->email,
            'expires_at' => now()->addHours(24)->timestamp,
            'attempts_remaining' => 3,
        ], now()->addHours(24));

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
     * Confirmar reset de contraseña y generar nuevos tokens
     *
     * Sigue el MISMO patrón que AuthService::login()
     * - Valida token/código
     * - Actualiza password
     * - Revoca todas las sesiones
     * - Genera nuevos tokens JWT
     * - Retorna array completo para GraphQL
     *
     * @param string $token
     * @param string $newPassword
     * @param array $deviceInfo [opcional]
     * @return array ['user' => User, 'access_token' => string, 'refresh_token' => string, 'expires_in' => int, 'session_id' => string]
     * @throws AuthenticationException
     */
    public function confirmReset(string $token, string $newPassword, array $deviceInfo = []): array
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

        // === GENERAR NUEVOS TOKENS (reutilizando patrón de AuthService) ===
        // RefreshToken PRIMERO para usar su ID como session_id
        $refreshTokenData = $this->tokenService->createRefreshToken($user, $deviceInfo);
        $sessionId = $refreshTokenData['model']->id;
        $accessToken = $this->tokenService->generateAccessToken($user, $sessionId);

        // Disparar evento
        event(new PasswordResetCompleted($user));

        return [
            'user' => $user->fresh(['profile']),
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenData['token'],
            'expires_in' => config('jwt.ttl') * 60,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Confirmar reset de contraseña usando código de 6 dígitos
     *
     * Sigue el MISMO patrón que confirmReset()
     *
     * @param string $code Código de 6 dígitos
     * @param string $newPassword
     * @param array $deviceInfo [opcional]
     * @return array ['user' => User, 'access_token' => string, 'refresh_token' => string, 'expires_in' => int, 'session_id' => string]
     * @throws AuthenticationException
     */
    public function confirmResetWithCode(string $code, string $newPassword, array $deviceInfo = []): array
    {
        // Validar que el código tenga exactamente 6 dígitos
        if (!preg_match('/^\d{6}$/', $code)) {
            throw new AuthenticationException('Invalid code format. Must be 6 digits.');
        }

        // Buscar usuario que tenga este código
        $userId = $this->findUserByResetCode($code);

        if (!$userId) {
            throw new AuthenticationException('Invalid or expired code');
        }

        // Buscar usuario
        $user = User::find($userId);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('User account is not active');
        }

        // Validar que la nueva contraseña sea diferente
        if (Hash::check($newPassword, $user->password_hash)) {
            throw new AuthenticationException('New password must be different from current password');
        }

        // Actualizar contraseña
        $user->password_hash = Hash::make($newPassword);
        $user->save();

        // Invalidar código
        $this->invalidateResetCode($code, $userId);

        // Invalidar token asociado si existe
        $this->invalidateAllResetTokensForUser($userId);

        // Revocar TODAS las sesiones del usuario (logout everywhere)
        $this->tokenService->revokeAllUserTokens($userId, $userId);

        // === GENERAR NUEVOS TOKENS (reutilizando patrón de AuthService) ===
        // RefreshToken PRIMERO para usar su ID como session_id
        $refreshTokenData = $this->tokenService->createRefreshToken($user, $deviceInfo);
        $sessionId = $refreshTokenData['model']->id;
        $accessToken = $this->tokenService->generateAccessToken($user, $sessionId);

        // Disparar evento
        event(new PasswordResetCompleted($user));

        return [
            'user' => $user->fresh(['profile']),
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenData['token'],
            'expires_in' => config('jwt.ttl') * 60,
            'session_id' => $sessionId,
        ];
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
     * Buscar usuario por código de reset
     *
     * Usa un mapeo inverso (code -> user_id) almacenado en cache
     * para búsqueda O(1) en lugar de iterar todas las keys
     *
     * @param string $code
     * @return string|null User ID (UUID) o null
     */
    private function findUserByResetCode(string $code): ?string
    {
        // Buscar user_id usando el mapeo inverso
        $cacheKey = "password_reset_code_lookup:{$code}";
        $userId = Cache::get($cacheKey);

        if ($userId) {
            return (string) $userId;
        }

        return null;
    }

    /**
     * Invalidar código de reset
     *
     * @param string $code
     * @param string $userId User ID (UUID)
     * @return bool
     */
    private function invalidateResetCode(string $code, string $userId): bool
    {
        // Eliminar ambas keys: user_id -> code Y code -> user_id
        Cache::forget("password_reset_code:{$userId}");
        Cache::forget("password_reset_code_lookup:{$code}");

        return true;
    }

    /**
     * Invalidar todos los tokens de reset para un usuario
     *
     * @param string $userId User ID (UUID)
     */
    private function invalidateAllResetTokensForUser(string $userId): void
    {
        // Buscar todos los tokens para este usuario usando el prefijo de Laravel
        $cachePrefix = config('cache.prefix', '');
        $pattern = $cachePrefix . 'password_reset:*';
        $allKeys = \Illuminate\Support\Facades\Redis::keys($pattern);

        foreach ($allKeys as $fullKey) {
            // Extraer la key sin el prefijo de Laravel
            if ($cachePrefix) {
                $key = str_replace($cachePrefix, '', $fullKey);
            } else {
                $key = $fullKey;
            }

            // Obtener datos del token
            $data = Cache::get($key);
            if ($data && isset($data['user_id']) && $data['user_id'] == $userId) {
                // Extraer solo el token de la key
                $token = str_replace('password_reset:', '', $key);
                $this->invalidateResetToken($token);
            }
        }
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
