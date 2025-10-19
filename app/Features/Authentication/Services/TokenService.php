<?php

namespace App\Features\Authentication\Services;

use App\Features\Authentication\Models\RefreshToken;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\Authentication\Exceptions\TokenExpiredException;
use App\Features\UserManagement\Models\User;
use App\Shared\Exceptions\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * TokenService
 *
 * Servicio para manejo de JWT tokens (access + refresh).
 *
 * Responsabilidades:
 * - Generar access tokens JWT con claims personalizados
 * - Crear refresh tokens con hash SHA-256
 * - Validar y decodificar tokens
 * - Rotación de refresh tokens
 * - Revocación de tokens (individual o global por usuario)
 * - Blacklist de tokens para logout inmediato
 */
class TokenService
{
    /**
     * Generar access token JWT
     *
     * @param User $user
     * @param string|null $sessionId ID de sesión único para este login
     * @return string JWT token
     */
    public function generateAccessToken(User $user, ?string $sessionId = null): string
    {
        $now = time();
        $ttl = (int) config('jwt.ttl') * 60; // TTL en segundos

        $payload = [
            // Claims estándar
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => $user->id,

            // Claims personalizados
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => $sessionId ?? Str::uuid()->toString(),
        ];

        return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
    }

    /**
     * Crear refresh token en DB
     *
     * @param User $user
     * @param array $deviceInfo ['name' => string, 'ip' => string, 'user_agent' => string]
     * @return array ['token' => string, 'model' => RefreshToken]
     */
    public function createRefreshToken(User $user, array $deviceInfo = []): array
    {
        // Generar token aleatorio (64 caracteres hex)
        $token = bin2hex(random_bytes(32));

        // Hash SHA-256 para almacenar en DB (nunca guardar plain token)
        $tokenHash = hash('sha256', $token);

        // TTL desde config (en minutos)
        $refreshTtl = (int) config('jwt.refresh_ttl');

        // Crear registro en DB
        $refreshToken = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'device_name' => $deviceInfo['name'] ?? null,
            'ip_address' => $deviceInfo['ip'] ?? null,
            'user_agent' => $deviceInfo['user_agent'] ?? null,
            'expires_at' => now()->addMinutes($refreshTtl),
        ]);

        return [
            'token' => $token, // Token plano (se envía al cliente UNA VEZ)
            'model' => $refreshToken,
        ];
    }

    /**
     * Validar y decodificar access token
     *
     * @param string $token
     * @return object Payload decodificado
     * @throws AuthenticationException
     */
    public function validateAccessToken(string $token): object
    {
        try {
            // Decodificar con verificación de firma
            $decoded = JWT::decode(
                $token,
                new Key(config('jwt.secret'), config('jwt.algo'))
            );

            // Verificar claims requeridos
            $requiredClaims = config('jwt.required_claims');
            foreach ($requiredClaims as $claim) {
                if (!isset($decoded->$claim)) {
                    throw TokenInvalidException::accessToken();
                }
            }

            // Verificar si está en blacklist (logout simple)
            if ($this->isTokenBlacklisted($decoded->session_id ?? '')) {
                throw TokenInvalidException::accessToken();
            }

            // Verificar si el usuario está en blacklist global (logout everywhere)
            if ($this->isUserBlacklisted($decoded->user_id, $decoded->iat)) {
                throw TokenInvalidException::accessToken();
            }

            return $decoded;
        } catch (TokenInvalidException | TokenExpiredException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw TokenExpiredException::accessToken();
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw TokenInvalidException::accessToken();
        } catch (\Firebase\JWT\BeforeValidException $e) {
            throw TokenInvalidException::accessToken();
        } catch (\Exception $e) {
            throw TokenInvalidException::accessToken();
        }
    }

    /**
     * Validar refresh token
     *
     * @param string $token Plain refresh token
     * @return RefreshToken
     * @throws AuthenticationException
     */
    public function validateRefreshToken(string $token): RefreshToken
    {
        $tokenHash = hash('sha256', $token);

        $refreshToken = RefreshToken::where('token_hash', $tokenHash)->first();

        if (!$refreshToken) {
            throw TokenInvalidException::refreshToken();
        }

        if ($refreshToken->isExpired()) {
            throw TokenExpiredException::refreshToken();
        }

        if ($refreshToken->isRevoked()) {
            throw TokenInvalidException::refreshToken();
        }

        if (!$refreshToken->user->isActive()) {
            throw TokenInvalidException::refreshToken();
        }

        return $refreshToken;
    }

    /**
     * Renovar access token usando refresh token
     * Implementa rotación de refresh tokens (invalida el viejo, crea uno nuevo)
     *
     * @param string $refreshTokenPlain
     * @param array $deviceInfo
     * @return array ['access_token' => string, 'refresh_token' => string, 'expires_in' => int]
     * @throws AuthenticationException
     */
    public function refreshAccessToken(string $refreshTokenPlain, array $deviceInfo = []): array
    {
        // Validar refresh token
        $oldRefreshToken = $this->validateRefreshToken($refreshTokenPlain);

        // Actualizar timestamp de último uso
        $oldRefreshToken->updateLastUsed();

        $user = $oldRefreshToken->user;

        // ROTACIÓN: Invalidar refresh token viejo y crear uno nuevo
        $oldRefreshToken->revoke($user->id);
        $newRefreshTokenData = $this->createRefreshToken($user, $deviceInfo);

        // Generar nuevo access token usando el ID del nuevo RefreshToken como session_id
        $sessionId = $newRefreshTokenData['model']->id;
        $accessToken = $this->generateAccessToken($user, $sessionId);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshTokenData['token'],
            'expires_in' => config('jwt.ttl') * 60, // En segundos
        ];
    }

    /**
     * Revocar un refresh token específico
     *
     * @param string $token Plain refresh token
     * @param string|null $revokedById
     * @return void
     * @throws AuthenticationException
     */
    public function revokeRefreshToken(string $token, ?string $revokedById = null): void
    {
        $tokenHash = hash('sha256', $token);

        $refreshToken = RefreshToken::where('token_hash', $tokenHash)->first();

        if (!$refreshToken) {
            throw TokenInvalidException::refreshToken();
        }

        $refreshToken->revoke($revokedById);
    }

    /**
     * Revocar todos los refresh tokens de un usuario
     * Útil para "Logout from all devices"
     *
     * @param string $userId
     * @param string|null $revokedById
     * @return int Número de tokens revocados
     */
    public function revokeAllUserTokens(string $userId, ?string $revokedById = null): int
    {
        return RefreshToken::revokeAllForUser($userId, $revokedById);
    }

    /**
     * Agregar token a blacklist (logout inmediato)
     *
     * @param string $sessionId
     * @param int|null $ttl Tiempo de vida en segundos (default: hasta expiración natural del token)
     * @return void
     */
    public function blacklistToken(string $sessionId, ?int $ttl = null): void
    {
        if (!config('jwt.blacklist_enabled')) {
            return;
        }

        $ttl = $ttl ?? config('jwt.ttl') * 60;

        Cache::put(
            $this->getBlacklistKey($sessionId),
            true,
            now()->addSeconds($ttl)
        );
    }

    /**
     * Verificar si un token está en blacklist
     *
     * @param string $sessionId
     * @return bool
     */
    public function isTokenBlacklisted(string $sessionId): bool
    {
        if (!config('jwt.blacklist_enabled')) {
            return false;
        }

        return Cache::has($this->getBlacklistKey($sessionId));
    }

    /**
     * Agregar usuario a blacklist global (logout everywhere)
     * Invalida todos los access tokens del usuario generados antes de este momento
     *
     * @param string $userId
     * @return void
     */
    public function blacklistUser(string $userId): void
    {
        if (!config('jwt.blacklist_enabled')) {
            return;
        }

        // Guardar timestamp actual - todos los tokens anteriores quedan inválidos
        $ttl = config('jwt.ttl') * 60; // Duración del access token en segundos

        Cache::put(
            $this->getUserBlacklistKey($userId),
            time(), // Timestamp actual
            now()->addSeconds($ttl + 300) // +5 min de margen para limpieza
        );
    }

    /**
     * Verificar si un usuario está en blacklist global
     *
     * @param string $userId
     * @param int $tokenIssuedAt Timestamp 'iat' del token
     * @return bool
     */
    public function isUserBlacklisted(string $userId, int $tokenIssuedAt): bool
    {
        if (!config('jwt.blacklist_enabled')) {
            return false;
        }

        $blacklistedAt = Cache::get($this->getUserBlacklistKey($userId));

        if (!$blacklistedAt) {
            return false;
        }

        // Si el token fue emitido antes o igual al blacklist, está invalidado
        return $tokenIssuedAt <= $blacklistedAt;
    }

    /**
     * Extraer información del token sin validar firma
     * ADVERTENCIA: Solo usar para debugging o logs, NO para autenticación
     *
     * @param string $token
     * @return object|null
     */
    public function decodeWithoutVerification(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode($parts[1]));
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Limpiar refresh tokens expirados (garbage collection)
     * Ejecutar periódicamente con scheduler
     *
     * @return int Número de tokens eliminados
     */
    public function cleanExpiredTokens(): int
    {
        return RefreshToken::cleanExpired();
    }

    /**
     * Obtener información del dispositivo desde request
     *
     * @param \Illuminate\Http\Request|null $request
     * @return array
     */
    public function getDeviceInfo($request = null): array
    {
        $request = $request ?? request();

        return [
            'name' => $this->detectDeviceName($request->userAgent()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Generar key para blacklist en cache
     */
    private function getBlacklistKey(string $sessionId): string
    {
        return "jwt_blacklist:{$sessionId}";
    }

    /**
     * Generar key para blacklist de usuario en cache
     */
    private function getUserBlacklistKey(string $userId): string
    {
        return "jwt_user_blacklist:{$userId}";
    }

    /**
     * Detectar nombre del dispositivo desde user agent
     */
    private function detectDeviceName(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Detección de navegador
        $browser = 'Unknown Browser';
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }

        // Detección de sistema operativo
        $os = 'Unknown OS';
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        return "{$browser} on {$os}";
    }
}