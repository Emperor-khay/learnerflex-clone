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

        // If there's no authenticated user, deny access
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check role-based access
        if ($role === 'admin' && $user->role === 'admin') {
            return $next($request);
        }

        if ($role === 'vendor' && $user->role === 'vendor') {
            return $next($request);
        }

        if ($role === 'affiliate' && $user->role === 'affiliate') {
            return $next($request);
        }

        // If the role doesn't match, deny access
        return response()->json(['message' => 'Access Denied'], 403);
    }
       
}
