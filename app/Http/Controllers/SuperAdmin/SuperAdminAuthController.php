<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;

class SuperAdminAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // Validate the login request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to find the super admin by email
        $superAdmin = SuperAdmin::where('email', $request->email)->first();

        // Check if the super admin exists and if the password is correct
        if (! $superAdmin || ! Hash::check($request->password, $superAdmin->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Generate a token for the authenticated super admin
        $token = $superAdmin->createToken('superAdminToken')->plainTextToken;

        // Return a structured response with the token, super admin data, and success message
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
            ]
        ], 200);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
