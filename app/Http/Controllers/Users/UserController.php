<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        try {
            $users = $this->userService->getAllUsers();
            return $this->success($users, 'Retrieved all users!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function transactions(User $user)
    {
        try {
            $users = $this->userService->getTransactionsForUser($user);
            return $this->success($users, 'User transactions!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function displayCurrency(User $user, Request $request)
    {
        try {
            $users = $this->userService->updateUserCurrency($user, $request->input('currency'));
            return $this->success($users, 'user currency updated!', 201);
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
