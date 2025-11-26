<?php declare(strict_types=1);

namespace App\Shared\Traits;

use App\Features\Authentication\Exceptions\TokenExpiredException;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\Authentication\Services\TokenService;
use App\Features\UserManagement\Models\User;
use App\Shared\Enums\UserStatus;
use App\Shared\Exceptions\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * JWT Authentication Trait
 *
 * Trait reutilizable que proporciona funcionalidad común de autenticación JWT.
 * Permite que cualquier middleware o servicio use la misma lógica de autenticación
 * de manera consistente y escalable.
 *
 * CARACTERÍSTICAS PROFESIONALES:
 * - ✅ Reutilizable: Cualquier clase puede usar este trait
 * - ✅ Consistente: Misma lógica de autenticación en toda la app
 * - ✅ Escalable: Fácil de mantener y extender
 * - ✅ Logging: Logging estructurado para observabilidad
 * - ✅ Error Handling: Integración con sistema de errores existente
 *
 * USO:
 * ```php
 * class MyMiddleware
 * {
 *     use JWTAuthenticationTrait;
 *
 *     public function handle(Request $request, Closure $next)
 *     {
 *         $user = $this->authenticateUser($request);
 *         // ... resto de la lógica
 *     }
 * }
 * ```
 *
 * @package App\Shared\Traits
 */
trait JWTAuthenticationTrait
{
    /**
     * Authenticate user from JWT token
     *
     * @param Request $request
     * @return User|null The authenticated user or null if no token
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws AuthenticationException
     */
    protected function authenticateUser(Request $request): ?User
    {
        $token = $this->extractJWTToken($request);

        if (!$token) {
            return null;
        }

        return $this->processJWTToken($request, $token);
    }

    /**
     * Authenticate user from JWT token (throws exception if no token)
     *
     * @param Request $request
     * @return User The authenticated user
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws AuthenticationException
     */
    protected function requireAuthentication(Request $request): User
    {
        $user = $this->authenticateUser($request);

        if (!$user) {
            throw AuthenticationException::unauthenticated();
        }

        return $user;
    }

    /**
     * Extract JWT token from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractJWTToken(Request $request): ?string
    {
        // Primero intentar obtener del header Authorization (API calls)
        $header = $request->header('Authorization');

        if ($header) {
            // Remove "Bearer " prefix if present
            if (stripos($header, 'Bearer ') === 0) {
                return substr($header, 7);
            }
            return $header;
        }

        // Si no hay header Authorization, intentar desde la cookie jwt_token
        // (para rutas web/Blade que requieren autenticación)
        $cookieToken = $request->cookie('jwt_token');
        if ($cookieToken) {
            return $cookieToken;
        }

        return null;
    }

    /**
     * Process JWT token and return authenticated user
     *
     * @param Request $request
     * @param string $token
     * @return User
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     * @throws AuthenticationException
     */
    protected function processJWTToken(Request $request, string $token): User
    {
        $tokenService = app(TokenService::class);

        // Validate token and get payload
        $payload = $tokenService->validateAccessToken($token);

        // Extract user ID from payload
        $userId = $payload->user_id ?? null;

        if (!$userId) {
            throw AuthenticationException::userNotFound();
        }

        // Load user with roles
        $user = User::with('activeRoles')->find($userId);

        if (!$user) {
            throw AuthenticationException::userNotFound();
        }

        // Check user status
        if ($user->status === UserStatus::SUSPENDED) {
            throw AuthenticationException::accountSuspended();
        }

        // Store user in request attributes for downstream use
        $this->storeAuthenticatedUser($request, $user, $payload);

        return $user;
    }

    /**
     * Store authenticated user in request attributes
     *
     * @param Request $request
     * @param User $user
     * @param object $payload
     * @return void
     */
    protected function storeAuthenticatedUser(Request $request, User $user, object $payload): void
    {
        $request->attributes->set('jwt_user', $user);
        // CRITICAL FIX: Convert payload to array using json_encode/decode
        // This handles nested stdClass objects that appear in JWT payload
        // json_encode converts stdClass to JSON, then json_decode with assoc=true converts back to arrays
        $payloadArray = json_decode(json_encode($payload), true);
        $request->attributes->set('jwt_payload', $payloadArray);
        $request->attributes->set('jwt_user_id', $user->id);

        // CRITICAL: Also inject into request for compatibility
        $request->merge(['_authenticated_user_id' => $user->id]);

        // CRITICAL: Set user resolver so $request->user() works
        $request->setUserResolver(fn() => $user);

        // CRITICAL: Also set in auth() helper for compatibility
        auth()->setUser($user);
    }

    /**
     * Log JWT authentication attempt
     *
     * @param Request $request
     * @param string $action
     * @param bool $success
     * @param \Throwable|null $exception
     * @param float $duration
     * @return void
     */
    protected function logJWTAuthentication(
        Request $request,
        string $action,
        bool $success,
        ?\Throwable $exception = null,
        float $duration = 0
    ): void {
        $logContext = [
            'trait' => 'JWTAuthenticationTrait',
            'action' => $action,
            'success' => $success,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($exception) {
            $logContext['exception_class'] = get_class($exception);
            $logContext['exception_message'] = $exception->getMessage();
        }

        if ($success) {
            Log::info('JWT Authentication successful', $logContext);
        } else {
            Log::warning('JWT Authentication failed', $logContext);
        }
    }

    /**
     * Get authenticated user from request attributes
     *
     * @param Request $request
     * @return User|null
     */
    protected function getAuthenticatedUser(Request $request): ?User
    {
        return $request->attributes->get('jwt_user');
    }

    /**
     * Check if request has authenticated user
     *
     * @param Request $request
     * @return bool
     */
    protected function hasAuthenticatedUser(Request $request): bool
    {
        return $request->attributes->has('jwt_user');
    }

    /**
     * Get user ID from request attributes
     *
     * @param Request $request
     * @return string|null
     */
    protected function getAuthenticatedUserId(Request $request): ?string
    {
        return $request->attributes->get('jwt_user_id');
    }

    /**
     * Get JWT payload from request attributes
     *
     * @param Request $request
     * @return array|null
     */
    protected function getJWTPayload(Request $request): ?array
    {
        return $request->attributes->get('jwt_payload');
    }
}
