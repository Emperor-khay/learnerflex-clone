<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\VendorRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Service\UserService;
use App\Service\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    protected $userService;
    protected $vendorService;

    public function __construct(UserService $userService, VendorService $vendorService)
    {
        $this->userService = $userService;
        $this->vendorService = $vendorService;
    }

    public function index(User $user): JsonResponse
    {
        try {
            $vendors = $this->userService->getUserVendors($user);
            return $this->success($vendors, 'Retrieved all user vendors!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }

    public function store(VendorRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                $path = $request->file('photo')->store('images/vendors', 'public');
                $validatedData['photo'] = $path;
            } else {
                $validatedData['photo'] = null;
            }
            $user = $request->user();
            $result = $this->userService->createVendorForUser($user, $validatedData);
            $result['photo'] = $result->photo ? Storage::url($result->photo) : null;
            return $this->success($result, 'Vendor Created!', Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            Log::error('Vendor creation failed', ['error' => $th->getMessage()]);
            return $this->error([], $th->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function delete(int $vendor_id): JsonResponse
    {
        try {
            $vendor = $this->vendorService->deleteVendor($vendor_id);
            return $this->success($vendor, 'deleted vendor!');
        } catch (\Throwable $th) {
            return $this->error([], $th->getMessage(), 400);
        }
    }
}
