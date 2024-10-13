<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        // Retrieve the authenticated user
        $user = $request->user();

        // Check if the user is authenticated and has the appropriate role
        if ($user) {
            // Admins have access to all routes
            if ($user->role === 'admin') {
                return $next($request);
            }

            // Vendors can access vendor and affiliate routes
            if ($user->role === 'vendor' && ($role === 'vendor' || $role === 'affiliate')) {
                return $next($request);
            }

            // Affiliates can access only affiliate routes
            if ($user->role === 'affiliate' && $role === 'affiliate') {
                return $next($request);
            }
        }

        // Return an unauthorized response if role check fails
        return response()->json(['message' => 'Access Denied'], 403);
    }
}
