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
        private readonly TokenService $tokenService
    ) {}

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

        } catch (TokenExpiredException $e) {
            throw AuthenticationException::tokenExpired();
        } catch (TokenInvalidException $e) {
            throw AuthenticationException::tokenInvalid();
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentication failed: ' . $e->getMessage());
        }
    }
}
