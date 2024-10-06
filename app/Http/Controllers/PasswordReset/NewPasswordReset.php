<?php

namespace App\Http\Controllers\PasswordReset;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetSuccess;
use App\Models\User;

class NewPasswordReset extends Controller
{


public function resetPassword(Request $request)
{
    // Validate the request data
    $request->validate([
        'email' => 'required|email',
        'token' => 'required|string',
        'password' => 'required|string|min:8',
    ]);


    // Find the user by email
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    
    // Update the user's password
    $user->password = Hash::make($request->password);
    $user->save();

    // Delete the reset token
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();


    return response()->json(['message' => 'Password reset successful'], 200);
}

}
