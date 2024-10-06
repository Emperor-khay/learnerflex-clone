<?php

namespace App\Http\Controllers\PasswordReset;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function sendPasswordResetLink(Request $request)
    {
        // Validate the request data
        $request->validate(['email' => 'required|email']);
        
        // Find the user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
             return response()->json(['message' => 'User not found'], 404);
        }

        // Generate a 4-digit random number
        $token = mt_rand(1000, 9999);
        
            

        // Store the token in the password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        Mail::to($user->email)->send(new \App\Mail\PasswordResetLink($token));
        
        return response()->json(['success' => true, 'message' => 'Password reset link sent successfully'], 200);
    }
}
