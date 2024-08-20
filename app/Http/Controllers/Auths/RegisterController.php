<?php

namespace App\Http\Controllers\Auths;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Service\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RegisterController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());
            return $this->success($result, 'Onboarding Successful', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
