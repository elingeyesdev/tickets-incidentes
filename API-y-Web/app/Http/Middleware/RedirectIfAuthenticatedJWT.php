<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticatedJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the JWT cookie OR the Refresh Token cookie exists
        // If either exists, we send them to the Auth Loader to try and restore the session.
        if ($request->hasCookie('jwt_token') || $request->hasCookie('refresh_token')) {
            \Log::info('[RedirectIfAuthenticatedJWT] Found auth cookies, redirecting to root', [
                'jwt' => $request->hasCookie('jwt_token'),
                'refresh' => $request->hasCookie('refresh_token')
            ]);
            return redirect()->route('root');
        }

        return $next($request);
    }
}
