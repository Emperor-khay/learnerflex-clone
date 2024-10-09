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

        // Check if the user has the required role, or allow vendor access to affiliate routes
        if ($user && ($user->role === $role || ($role === 'affiliate' && $user->role === 'vendor'))) {
            return $next($request);
        }

        // Return an unauthorized response if role check fails
        return response()->json(['message' => 'Access Denied'], 403);
    }
}
