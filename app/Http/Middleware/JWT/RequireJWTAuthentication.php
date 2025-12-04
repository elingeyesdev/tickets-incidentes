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
        \Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Request received', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'expects_json' => $request->expectsJson(),
            'has_auth_header' => $request->hasHeader('Authorization'),
            'has_jwt_cookie' => $request->hasCookie('jwt_token'),
            'has_refresh_cookie' => $request->hasCookie('refresh_token'),
        ]);
        
        try {
            // Extract token from header
            $token = $this->extractJWTToken($request);

            \Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Token extraction result', [
                'token_found' => !!$token,
                'token_length' => $token ? strlen($token) : 0,
                'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
            ]);

            // REQUIRED: Token must be present
            if (!$token) {
                \Illuminate\Support\Facades\Log::warning('[JWT MIDDLEWARE] No token found, throwing unauthenticated exception');
                throw AuthenticationException::unauthenticated();
            }

            // Authenticate user with token
            \Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Attempting to authenticate user with token');
            $this->authenticateUser($request);
            
            \Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Authentication successful, proceeding with request');
            return $next($request);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('[JWT MIDDLEWARE] Authentication exception caught', [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'expects_json' => $request->expectsJson(),
            ]);
            
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


            // SMART CUSTOMS LOGIC (ADUANA INTELIGENTE)
            // Instead of trying to fix it here, we send the user to the Central Auth Loader (/)
            // The loader will check if they have a valid Refresh Token, fix the session, and redirect back.
            
            \Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Web request unauthenticated. Redirecting to Auth Loader.', [
                'redirect_to' => $request->fullUrl()
            ]);

            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect()->route('root', ['redirect_to' => $request->fullUrl()])
            );
        }
    }
}
