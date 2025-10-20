<?php

declare(strict_types=1);

namespace App\Http\Middleware\JWT;

use App\Features\Authentication\Exceptions\TokenExpiredException;
use App\Features\Authentication\Exceptions\TokenInvalidException;
use App\Features\Authentication\Services\TokenService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Traits\JWTAuthenticationTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * JWT Authentication Middleware
 *
 * Validates JWT access tokens and loads the authenticated user.
 * Stores user information in request attributes for downstream use.
 *
 * @package App\Http\Middleware\JWT
 */
class JWTAuthenticationMiddleware
{
    use JWTAuthenticationTrait;

    /**
     * Create a new middleware instance.
     *
     * @param TokenService $tokenService Service for JWT token operations
     */
    public function __construct(
        private readonly TokenService $tokenService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * PROFESSIONAL INTEGRATION:
     * - Uses existing error handling system (Shared\Exceptions)
     * - Structured logging for observability
     * - Granular error handling by exception type
     * - Maintains backward compatibility
     * - Scalable and reusable architecture
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware closure
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $token = $this->extractJWTToken($request);
        
        try {
            // IMPORTANT: This middleware is OPTIONAL
            // If no token is present, we just continue without authenticating
            // GraphQL directives (@jwt) will enforce authentication on specific fields
            if ($token) {
                $this->authenticateUser($request);
            }

            // Log successful authentication (for monitoring)
            $this->logJWTAuthentication($request, 'middleware_authentication', true, null, microtime(true) - $startTime);

            return $next($request);

        } catch (TokenExpiredException $e) {
            // TOKEN EXPIRED: User needs to refresh token
            $this->logJWTAuthentication($request, 'middleware_authentication', false, $e, microtime(true) - $startTime);
            $this->storeAuthenticationError($request, 'TOKEN_EXPIRED', $e->getMessage());
            
            return $next($request);

        } catch (TokenInvalidException $e) {
            // TOKEN INVALID: Possible attack or corrupted token
            $this->logJWTAuthentication($request, 'middleware_authentication', false, $e, microtime(true) - $startTime);
            $this->storeAuthenticationError($request, 'TOKEN_INVALID', $e->getMessage());
            
            return $next($request);

        } catch (\Exception $e) {
            // UNKNOWN ERROR: Technical issue
            $this->logJWTAuthentication($request, 'middleware_authentication', false, $e, microtime(true) - $startTime);
            $this->storeAuthenticationError($request, 'UNKNOWN_ERROR', 'Authentication failed');
            
            return $next($request);
        }
    }


    /**
     * Store authentication error in request attributes
     *
     * @param Request $request
     * @param string $errorType
     * @param string $message
     * @return void
     */
    private function storeAuthenticationError(Request $request, string $errorType, string $message): void
    {
        $request->attributes->set('jwt_error', $message);
        $request->attributes->set('jwt_error_type', $errorType);
    }


}
