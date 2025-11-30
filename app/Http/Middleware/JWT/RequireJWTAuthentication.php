<?php declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use App\Features\Authentication\Exceptions\TokenExpiredException;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Traits\JWTAuthenticationTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require JWT Authentication Middleware
 *
 * OBLIGATORY JWT validation middleware.
 * If no token is present or invalid, returns 401 Unauthorized.
 *
 * Unlike JWTAuthenticationMiddleware which is OPTIONAL,
 * this middleware REQUIRES a valid JWT token.
 *
 * Usage in routes:
 * ```php
 * Route::middleware('jwt.require')->group(function () {
 *     Route::get('/protected', ...);
 * });
 * ```
 *
 * @package App\Http\Middleware\JWT
 */
class RequireJWTAuthentication
{
    use JWTAuthenticationTrait;

    public function __construct(
        private readonly TokenService $tokenService,
        private readonly \App\Features\Authentication\Services\AuthService $authService
    ) {
    }

    /**
     * Handle an incoming request.
     * REQUIRES valid JWT token - throws 401 if missing or invalid.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws AuthenticationException If token is missing or invalid
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Extract token from header
            $token = $this->extractJWTToken($request);

            // REQUIRED: Token must be present
            if (!$token) {
                throw AuthenticationException::unauthenticated();
            }

            // Authenticate user with token
            $this->authenticateUser($request);

            return $next($request);

        } catch (\Exception $e) {
            // If request expects JSON (API), rethrow to let ExceptionHandler handle it (returns 401 JSON)
            if ($request->expectsJson()) {
                if ($e instanceof TokenExpiredException) {
                    throw AuthenticationException::tokenExpired();
                }
                if ($e instanceof TokenInvalidException) {
                    throw AuthenticationException::tokenInvalid();
                }
                if ($e instanceof AuthenticationException) {
                    throw $e;
                }
                throw new AuthenticationException('Authentication failed: ' . $e->getMessage());
            }


            // SERVER-SIDE AUTO-REFRESH (For Web Requests)
            // If authentication failed (expired/missing), try to refresh using the HttpOnly cookie
            $refreshToken = $request->cookie('refresh_token');

            if ($refreshToken) {

                try {
                    // Attempt to refresh token
                    $deviceInfo = \App\Shared\Helpers\DeviceInfoParser::fromRequest($request);
                    $result = $this->authService->refreshToken($refreshToken, $deviceInfo);

                    // If successful, we have a new access token
                    $newAccessToken = $result['access_token'];

                    \Illuminate\Support\Facades\Log::info('Refresh successful, authenticating with new token');

                    // Manually authenticate the user with the new token so the request can proceed
                    $this->processJWTToken($request, $newAccessToken);

                    // CRITICAL: Store the refreshed token in request so Blade can inject it into the page
                    // This prevents the frontend from seeing an expired token in localStorage
                    $request->attributes->set('server_refreshed_token', [
                        'access_token' => $newAccessToken,
                        'expires_in' => $result['expires_in'],
                    ]);

                    // Proceed with the request
                    $response = $next($request);

                    // Attach new cookies to the response
                    $cookieLifetime = (int) config('jwt.refresh_ttl');
                    $secure = config('app.env') === 'production';

                    // 1. New Access Token Cookie (Not Encrypted, for JS)
                    $response->withCookie(cookie(
                        'jwt_token',
                        $newAccessToken,
                        $result['expires_in'] / 60, // Minutes
                        '/',
                        null,
                        $secure,
                        false, // Not HttpOnly (JS needs it)
                        false, // Raw
                        'lax'
                    ));

                    // 2. New Refresh Token Cookie (HttpOnly, Encrypted)
                    $response->withCookie(cookie(
                        'refresh_token',
                        $result['refresh_token'],
                        $cookieLifetime,
                        '/',
                        null,
                        $secure,
                        true, // HttpOnly
                        false,
                        'strict'
                    ));

                    return $response;

                } catch (\Exception $refreshError) {
                    // Refresh failed (invalid/expired refresh token or token validation error)
                    // Log and fall through to redirect
                    \Illuminate\Support\Facades\Log::warning('Server-side auto-refresh failed', [
                        'error' => $refreshError->getMessage(),
                        'type' => get_class($refreshError)
                    ]);
                    // Fall through to redirect below
                }
            }

            // If it's a Web request (Browser) and refresh failed, redirect to login AND CLEAR COOKIE
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('login', ['reason' => 'session_expired'])
                    ->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.')
                    ->withCookie(cookie()->forget('jwt_token'))
            );
        }
    }
}
