<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\WantVendorRequest;
use App\Mail\VendorAccountWanted;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    public function handleUserImage(Request $request)
    {
        try {
            $data = $request->all();
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('images/users', 'public');
                $data['image'] = $path;
            } else {
                $data['image'] = null;
            }
            $user = $request->user();
            $result = $this->userService->updateUserImage($user, $data['image']);
            $result['image'] = $result->image ? Storage::url($result->image) : null;
            if ($user->image) {
                Storage::disk('public')->delete($user->photo);
            }
            return $this->success($result, 'profile image updated!', 201);
        } catch (\Throwable $th) {
            Log::error("user image update error: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function handleUserProfile(UpdateProfileRequest $updateProfileRequest)
    {
        try {
            $profile = $updateProfileRequest->validated();
            if(empty($profile)){
                return $this->error([], 'Missing details!', 400);
            }
            $user = $this->userService->updateUserDetails($updateProfileRequest->user(), $profile);
            return $this->success($user, 'Profile updated!', 201);
        } catch (\Throwable $th) {
            Log::error("Profile update: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function handleVendorRequest(WantVendorRequest $wantVendorRequest)
    {
        try {
            $saleUrl = $wantVendorRequest->saleUrl;
            $user = $wantVendorRequest->user();
            $user = $this->userService->updateUserVendorApplication($user, $saleUrl);
            // send email to admins
            Mail::to(env('MAIL_FROM_ADDRESS'))->send(new VendorAccountWanted($user, $saleUrl));
            return $this->success($user, 'Vendor request Sent!');
        } catch (\Throwable $th) {
            Log::error("Vendor request: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function handleUserVendorStatus(User $user)
    {
        try {
            $user = $this->userService->updateUserVendorStatus($user);
            return $this->success($user, 'User Vendor Status Updated!');
        } catch (\Throwable $th) {
            Log::error("Vendor request: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
