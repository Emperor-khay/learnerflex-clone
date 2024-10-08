<?php
namespace App\Http\Controllers\SuperAdmin;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
    public function showLoginForm()
    {
        return view('superAdmin.login'); // A separate login view for super admin
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('superAdmin')->attempt($credentials)) {
            return redirect()->intended('/super-admin/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout()
    {
        Auth::guard('superAdmin')->logout();
        return redirect('/super-admin/login');
    }
}
