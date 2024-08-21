<?php

namespace App\Http\Controllers\Auths;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Service\AuthService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function attemptUser(LoginRequest $request): JsonResponse
    {
        try {
            $attempt = $request->validated();
            $result = $this->authService->login($attempt['email'], $attempt['password']);
            return $this->success($result, 'Login Successfully!');
        } catch (\Throwable $th) {
            return $this->error(null, $th->getMessage(), $th->getCode());
        }
    }
}
