<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Features\Authentication\Services\AuthService;
use App\Features\Authentication\Services\TokenService;
use Illuminate\Routing\Controller;
use App\Shared\Helpers\DeviceInfoParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAuthStatusController extends Controller
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuthService $authService
    ) {}

    /**
     * Check authentication status and refresh token if necessary.
     * This endpoint is called by the loading page to determine where to redirect.
     */
    public function check(Request $request)
    {
        $jwtToken = $request->cookie('jwt_token');
        $refreshToken = $request->cookie('refresh_token');

        Log::info('[AUTH CHECK] Checking status', [
            'has_jwt' => !empty($jwtToken),
            'has_refresh' => !empty($refreshToken),
            'ip' => $request->ip(),
            'all_cookies' => $request->cookies->all()
        ]);

        // 1. Check if we have a valid Access Token
        if ($jwtToken) {
            try {
                $payload = $this->tokenService->validateAccessToken($jwtToken);
                
                Log::info('[AUTH CHECK] Valid JWT found', ['user_id' => $payload->user_id]);
                
                return response()->json([
                    'status' => 'authenticated',
                    'access_token' => $jwtToken, // Send back so frontend can sync if needed
                    'expires_in' => $payload->exp - time(),
                    'user' => [
                        'id' => $payload->user_id,
                        'name' => $payload->name ?? null,
                        'email' => $payload->email ?? null,
                    ]
                ]);
            } catch (\Exception $e) {
                Log::info('[AUTH CHECK] JWT invalid/expired', ['error' => $e->getMessage()]);
                // Fall through to refresh logic
            }
        }

        // 2. If no valid Access Token, try to Refresh
        if ($refreshToken) {
            try {
                Log::info('[AUTH CHECK] Attempting refresh with refresh_token');
                
                $deviceInfo = DeviceInfoParser::fromRequest($request);
                $result = $this->authService->refreshToken($refreshToken, $deviceInfo);
                
                Log::info('[AUTH CHECK] Refresh successful');

                // Attach new cookies to response
                $cookieLifetime = (int) config('jwt.refresh_ttl');
                $secure = config('app.env') === 'production';

                return response()->json([
                    'status' => 'authenticated',
                    'access_token' => $result['access_token'],
                    'expires_in' => $result['expires_in'],
                    'refreshed' => true
                ])
                ->withCookie(cookie(
                    'jwt_token',
                    $result['access_token'],
                    $result['expires_in'] / 60,
                    '/',
                    null,
                    $secure,
                    false, // Not HttpOnly (JS needs it)
                    false,
                    'lax'
                ))
                ->withCookie(cookie(
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

            } catch (\Exception $e) {
                Log::warning('[AUTH CHECK] Refresh failed', ['error' => $e->getMessage()]);
                
                // Clear cookies if refresh failed
                return response()->json(['status' => 'guest', 'reason' => 'refresh_failed'])
                    ->withCookie(cookie()->forget('jwt_token'))
                    ->withCookie(cookie()->forget('refresh_token'));
            }
        }

        // 3. No tokens found
        Log::info('[AUTH CHECK] No tokens found, returning guest status');
        return response()->json(['status' => 'guest']);
    }
}
