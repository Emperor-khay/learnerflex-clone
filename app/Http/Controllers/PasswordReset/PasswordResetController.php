<?php

namespace App\Http\Controllers\PasswordReset;

use Carbon\Carbon;
use App\Models\User;
use App\Rules\ReCaptchaV3;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function sendPasswordResetLink(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'email' => 'required|email',
                'g-recaptcha-response' => ['required', new ReCaptchaV3('reset')]
            ]);

            // Find the user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Generate a 6-digit numeric token
            $token = mt_rand(100000, 999999);

            // Store the token in the password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token), // Hash the token for security
                    'created_at' => Carbon::now(),
                ]
            );

            // Send the token via email
            Mail::to($user->email)->send(new \App\Mail\PasswordResetLink($token, $user->name));

            return response()->json([
                'success' => true,
                'message' => 'Password reset token sent successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions for debugging
            Log::error('Error sending password reset link', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the password reset link. Please try again.',
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        // Perform validation outside the try block
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed', // Use 'confirmed' for password confirmation
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validatedData = $validator->validated();

            // Check if the reset token exists and matches
            $resetToken = DB::table('password_reset_tokens')
                ->where('email', $validatedData['email'])
                ->first();

            if (!$resetToken || !Hash::check($validatedData['token'], $resetToken->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                ], 400);
            }

            // Find the user by email
            $user = User::where('email', $validatedData['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Update the user's password
            $user->password = Hash::make($validatedData['password']);
            $user->save();

            // Delete the reset token
            DB::table('password_reset_tokens')
                ->where('email', $validatedData['email'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successful',
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error resetting password', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting the password. Please try again.',
            ], 500);
        }
    }
}
