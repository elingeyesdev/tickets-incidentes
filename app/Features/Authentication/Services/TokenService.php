<?php

namespace App\Features\Authentication\Services;

use App\Features\Authentication\Models\RefreshToken;
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
        $ttl = config('jwt.ttl') * 60; // TTL en segundos

        $payload = [
            // Standard claims
            'iss' => config('jwt.issuer'),
            'aud' => config('jwt.audience'),
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => $user->id,

            // Custom claims
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
        $refreshTtl = config('jwt.refresh_ttl');

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
            'token' => $token, // Plain token (se envía al cliente UNA VEZ)
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
                    throw new AuthenticationException("Missing required claim: {$claim}");
                }
            }

            // Verificar si está en blacklist (logout)
            if ($this->isTokenBlacklisted($decoded->session_id ?? '')) {
                throw new AuthenticationException('Token has been revoked');
            }

            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new AuthenticationException('Token has expired');
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new AuthenticationException('Invalid token signature');
        } catch (\Firebase\JWT\BeforeValidException $e) {
            throw new AuthenticationException('Token not yet valid');
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid token: ' . $e->getMessage());
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
            throw new AuthenticationException('Invalid refresh token');
        }

        if ($refreshToken->isExpired()) {
            throw new AuthenticationException('Refresh token has expired');
        }

        if ($refreshToken->isRevoked()) {
            throw new AuthenticationException('Refresh token has been revoked');
        }

        if (!$refreshToken->user->isActive()) {
            throw new AuthenticationException('User account is not active');
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

        // Generar nuevo access token
        $sessionId = Str::uuid()->toString();
        $accessToken = $this->generateAccessToken($user, $sessionId);

        // ROTACIÓN: Invalidar refresh token viejo y crear uno nuevo
        $oldRefreshToken->revoke($user->id);

        $newRefreshTokenData = $this->createRefreshToken($user, $deviceInfo);

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
            throw new AuthenticationException('Refresh token not found');
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
     * Detectar nombre del dispositivo desde user agent
     */
    private function detectDeviceName(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Browser detection
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

        // OS detection
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