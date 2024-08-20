<?php

namespace App\Http\Controllers\Auths;

use App\Http\Controllers\Controller;
use App\Service\AuthService;

class LogoutController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function logout()
    {
        try {
            $user = request()->user();
            $this->authService->logout($user);
            return $this->success(null, 'Logged out!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 422);
        }
    }
}
