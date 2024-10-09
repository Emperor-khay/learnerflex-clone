<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class SuperAdminAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Get only validated data
        $validated = $request->validated();

        // Attempt to find the user by email
        $user = User::where('email', $validated['email'])->first();

        // Check if the user exists and if the password is correct
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Check if the user is an admin
        if ($user->role !== 'admin') {
            // If the user is not an admin, check if they have made payment
            if (! $user->has_paid_onboard) {
                return response()->json([
                    'message' => 'Payment required to login.',
                    'error' => 'Payment required'
                ], 403); // 403 Forbidden since payment is required
            }
        }

        // Generate a token for the authenticated user (admin or paid users)
        $token = $user->createToken('userToken')->plainTextToken;

        // Return a structured response with the token, user data, and success message
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ], 200);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
