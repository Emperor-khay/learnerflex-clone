<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
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
}
