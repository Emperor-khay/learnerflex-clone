<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;

class SuperAdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $superAdmin = SuperAdmin::where('email', $request->email)->first();

        if (! $superAdmin || ! Hash::check($request->password, $superAdmin->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $superAdmin->createToken('superAdminToken')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}