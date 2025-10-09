<?php

namespace App\Features\Authentication\Services;

use App\Features\Authentication\Events\EmailVerified;
use App\Features\Authentication\Events\UserLoggedIn;
use App\Features\Authentication\Events\UserLoggedOut;
use App\Features\Authentication\Events\UserRegistered;
use App\Features\Authentication\Models\RefreshToken;
use App\Features\UserManagement\Models\Role;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Services\RoleService;
use App\Features\UserManagement\Services\UserService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AuthService
 *
 * Servicio principal de autenticación.
 *
 * Responsabilidades:
 * - Registro de usuarios con verificación de email
 * - Login con credenciales (email + password)
 * - Logout (individual y global)
 * - Renovación de tokens (delegado a TokenService)
 * - Verificación de email
 * - Gestión de sesiones (listar, revocar)
 */
class AuthService
{
    public function __construct(
        private TokenService $tokenService,
        private UserService $userService,
        private RoleService $roleService
    ) {
    }

    /**
     * Registrar nuevo usuario
     *
     * @param array $data ['email' => string, 'password' => string, 'first_name' => string, 'last_name' => string]
     * @param array $deviceInfo
     * @return array ['user' => User, 'access_token' => string, 'refresh_token' => string, 'requires_verification' => bool]
     * @throws ValidationException
     */
    public function register(array $data, array $deviceInfo = []): array
    {
        // Validar que el email no esté en uso
        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withField('email', 'Email already registered');
        }

        // Separar datos de usuario y perfil
        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified' => false,
            'terms_accepted' => $data['terms_accepted'] ?? true,
            'terms_version' => 'v2.1',
        ];

        $profileData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
            'theme' => $data['theme'] ?? 'light',
            'language' => $data['language'] ?? 'es',
            'timezone' => $data['timezone'] ?? 'America/La_Paz',
        ];

        // Crear usuario (UserService se encarga de profile)
        $user = $this->userService->createUser($userData, $profileData);

        // Asignar rol USER por defecto (sin empresa, según constraint de BD)
        $this->roleService->assignRoleToUser(
            userId: $user->id,
            roleCode: Role::USER,
            companyId: null,  // USER no requiere empresa
            assignedBy: null   // Auto-asignado en registro
        );

        // Generar tokens
        $sessionId = Str::uuid()->toString();
        $accessToken = $this->tokenService->generateAccessToken($user, $sessionId);
        $refreshTokenData = $this->tokenService->createRefreshToken($user, $deviceInfo);

        // Crear token de verificación de email
        $verificationToken = $this->createEmailVerificationToken($user);

        // Disparar evento (enviará email de verificación)
        event(new UserRegistered($user, $verificationToken));

        return [
            'user' => $user->fresh(['profile']),
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenData['token'],
            'expires_in' => config('jwt.ttl') * 60,
            'requires_verification' => !$user->hasVerifiedEmail(),
        ];
    }

    /**
     * Login con email y password
     *
     * @param string $email
     * @param string $password
     * @param array $deviceInfo
     * @return array ['user' => User, 'access_token' => string, 'refresh_token' => string]
     * @throws AuthenticationException
     */
    public function login(string $email, string $password, array $deviceInfo = []): array
    {
        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw AuthenticationException::invalidCredentials();
        }

        // Verificar password
        if (!Hash::check($password, $user->password_hash)) {
            throw AuthenticationException::invalidCredentials();
        }

        // Verificar que el usuario esté activo
        if (!$user->isActive()) {
            // Diferenciar entre suspendido y eliminado
            if ($user->isSuspended()) {
                throw AuthenticationException::accountSuspended();
            }
            throw AuthenticationException::invalidCredentials(); // Para deleted u otros estados
        }

        // Actualizar last_login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $deviceInfo['ip'] ?? request()->ip(),
        ]);

        // Generar tokens
        $sessionId = Str::uuid()->toString();
        $accessToken = $this->tokenService->generateAccessToken($user, $sessionId);
        $refreshTokenData = $this->tokenService->createRefreshToken($user, $deviceInfo);

        // Disparar evento
        event(new UserLoggedIn($user, $deviceInfo));

        return [
            'user' => $user->fresh(['profile']),
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenData['token'],
            'expires_in' => config('jwt.ttl') * 60,
            'session_id' => $sessionId,
        ];
    }

    /**
     * Logout (sesión actual)
     *
     * @param string $accessToken Access token a invalidar
     * @param string $refreshToken Refresh token a revocar
     * @param string $userId
     * @return void
     * @throws AuthenticationException
     */
    public function logout(string $accessToken, string $refreshToken, string $userId): void
    {
        // Decodificar access token para obtener session_id
        $payload = $this->tokenService->validateAccessToken($accessToken);

        // Agregar a blacklist (invalidación inmediata)
        $this->tokenService->blacklistToken($payload->session_id ?? '');

        // Revocar refresh token
        try {
            $this->tokenService->revokeRefreshToken($refreshToken, $userId);
        } catch (AuthenticationException $e) {
            // Si el refresh token ya no existe, no es error crítico
        }

        // Disparar evento
        $user = User::find($userId);
        if ($user) {
            event(new UserLoggedOut($user));
        }
    }

    /**
     * Logout de todas las sesiones (todos los dispositivos)
     *
     * @param string $userId
     * @return int Número de sesiones cerradas
     */
    public function logoutAllDevices(string $userId): int
    {
        // Revocar todos los refresh tokens
        $revokedCount = $this->tokenService->revokeAllUserTokens($userId, $userId);

        // Disparar evento
        $user = User::find($userId);
        if ($user) {
            event(new UserLoggedOut($user, ['all_devices' => true]));
        }

        return $revokedCount;
    }

    /**
     * Renovar access token usando refresh token
     *
     * @param string $refreshToken
     * @param array $deviceInfo
     * @return array ['access_token' => string, 'refresh_token' => string, 'expires_in' => int]
     * @throws AuthenticationException
     */
    public function refreshToken(string $refreshToken, array $deviceInfo = []): array
    {
        return $this->tokenService->refreshAccessToken($refreshToken, $deviceInfo);
    }

    /**
     * Revocar otra sesión (por token_hash)
     *
     * @param string $tokenHash Hash del refresh token a revocar
     * @param string $userId Usuario que solicita la revocación
     * @return void
     * @throws AuthenticationException
     */
    public function revokeOtherSession(string $tokenHash, string $userId): void
    {
        $refreshToken = RefreshToken::where('token_hash', $tokenHash)
            ->where('user_id', $userId)
            ->first();

        if (!$refreshToken) {
            throw new AuthenticationException('Session not found');
        }

        if ($refreshToken->isRevoked()) {
            throw new AuthenticationException('Session already revoked');
        }

        $refreshToken->revoke($userId);
    }

    /**
     * Obtener todas las sesiones activas del usuario
     *
     * @param string $userId
     * @param string|null $currentTokenHash Hash del token actual (para marcarlo)
     * @return array
     */
    public function getUserSessions(string $userId, ?string $currentTokenHash = null): array
    {
        $sessions = RefreshToken::forUser($userId)
            ->active()
            ->orderByLastUsed()
            ->get();

        return $sessions->map(function ($session) use ($currentTokenHash) {
            return [
                'id' => $session->id,
                'device_name' => $session->device_name,
                'ip_address' => $session->ip_address,
                'last_used_at' => $session->last_used_at,
                'created_at' => $session->created_at,
                'is_current' => $currentTokenHash ? $session->isCurrent($currentTokenHash) : false,
            ];
        })->toArray();
    }

    // ==================== EMAIL VERIFICATION ====================

    /**
     * Crear token de verificación de email
     *
     * @param User $user
     * @return string Token de verificación
     */
    public function createEmailVerificationToken(User $user): string
    {
        $token = Str::random(64);

        // Guardar en cache por 24 horas
        $key = $this->getEmailVerificationKey($user->id);
        Cache::put($key, $token, now()->addHours(24));

        return $token;
    }

    /**
     * Verificar email usando token
     *
     * Busca el usuario asociado al token en cache y verifica el email.
     * Esta es la implementación profesional estándar (GitHub, Google, etc.)
     *
     * @param string $token Token de verificación
     * @return User
     * @throws AuthenticationException
     */
    public function verifyEmail(string $token): User
    {
        // Buscar userId que tiene este token
        $userId = $this->findUserIdByVerificationToken($token);

        if (!$userId) {
            throw new AuthenticationException('Invalid or expired verification token');
        }

        $user = User::find($userId);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if ($user->hasVerifiedEmail()) {
            throw new AuthenticationException('Email already verified');
        }

        // Verificar que el token coincide
        $key = $this->getEmailVerificationKey($userId);
        $storedToken = Cache::get($key);

        if (!$storedToken || $storedToken !== $token) {
            throw new AuthenticationException('Invalid or expired verification token');
        }

        // Marcar email como verificado
        $user->markEmailAsVerified();

        // Eliminar token del cache
        Cache::forget($key);

        // Disparar evento
        event(new EmailVerified($user));

        return $user->fresh(['profile']);
    }

    /**
     * Reenviar email de verificación
     *
     * @param string $userId
     * @return string Nuevo token
     * @throws AuthenticationException
     */
    public function resendEmailVerification(string $userId): string
    {
        $user = User::find($userId);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        if ($user->hasVerifiedEmail()) {
            throw new AuthenticationException('Email already verified');
        }

        // Crear nuevo token
        $token = $this->createEmailVerificationToken($user);

        // Disparar evento (enviará email)
        event(new UserRegistered($user, $token));

        return $token;
    }

    /**
     * Obtener estado de verificación de email
     *
     * @param string $userId
     * @return array
     */
    public function getEmailVerificationStatus(string $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        return [
            'is_verified' => $user->hasVerifiedEmail(),
            'verified_at' => $user->email_verified_at,
            'email' => $user->email,
        ];
    }

    // ==================== AUTH STATUS ====================

    /**
     * Obtener información del usuario autenticado
     *
     * @param string $accessToken
     * @return array
     * @throws AuthenticationException
     */
    public function getAuthenticatedUser(string $accessToken): array
    {
        $payload = $this->tokenService->validateAccessToken($accessToken);

        $user = User::with(['profile'])
            ->find($payload->user_id);

        if (!$user) {
            throw new AuthenticationException('User not found');
        }

        return [
            'user' => $user,
            'session_id' => $payload->session_id ?? null,
            'token_expires_at' => $payload->exp ?? null,
        ];
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Generar key para verificación de email en cache
     */
    private function getEmailVerificationKey(string $userId): string
    {
        return "email_verification:{$userId}";
    }

    /**
     * Buscar userId asociado a un token de verificación
     *
     * Busca en todos los keys de email_verification:* en cache
     * para encontrar cuál userId tiene este token
     *
     * @param string $token Token a buscar
     * @return string|null UserId si se encuentra, null si no
     */
    private function findUserIdByVerificationToken(string $token): ?string
    {
        // Obtener todos los usuarios registrados recientemente (últimas 24 horas)
        // que aún no han verificado su email
        $recentUsers = User::where('email_verified', false)
            ->where('created_at', '>=', now()->subHours(24))
            ->pluck('id');

        // Buscar en cache cuál de estos usuarios tiene el token
        foreach ($recentUsers as $userId) {
            $key = $this->getEmailVerificationKey($userId);
            $storedToken = Cache::get($key);

            if ($storedToken === $token) {
                return $userId;
            }
        }

        return null;
    }
}
