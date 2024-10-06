<?php

namespace App\Http\Controllers\PasswordReset;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PasswordResetController extends Controller
{
    public function sendPasswordResetLink(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Password reset link sent successfully'], 200);
    }
}
