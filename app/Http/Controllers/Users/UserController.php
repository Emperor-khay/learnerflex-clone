<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $user = $this->userService->updateUserCurrency($user, $request->input('currency'));
            return $this->success($user, 'user currency updated!', 201);
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function handleUserImage(User $user, Request $request)
    {
        try {
            $data = $request->all();
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('images/users', 'public');
                $data['image'] = $path;
            } else {
                $data['image'] = null;
            }
            $user = $this->userService->updateUserImage($user, $data['image']);
            $user['image'] = $user->image ? Storage::url($user->image) : null;
            return $this->success($user, 'profile image updated!', 201);
        } catch (\Throwable $th) {
            Log::error("user image update error: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
