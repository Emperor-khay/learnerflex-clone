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
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Models\Sale;

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
            // Use the paginate method to get 20 users per page
            $users = $this->userService->getAllUsers()->paginate(20);

            // Return the paginated users with metadata
            return response()->json([
                'success' => true,
                'message' => 'Retrieved all users!',
                'data' => $users->items(), // Only the data (users) without metadata
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
                'data' => [],
            ], 400);
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
            if (empty($profile)) {
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
            Mail::to('learnerflexltd@gmail.com')->send(new VendorAccountWanted($user, $saleUrl));
            return $this->success($user, 'Vendor request Sent!');
        } catch (\Throwable $th) {
            Log::error("Vendor request: $th");
            return $this->error([], $th->getMessage(), 400);
        }
    }


    public function getBalance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);


        $checkWithdrawHistory = Withdrawal::where('user_id', $request->user_id)->exists();

        if ($checkWithdrawHistory) {
            $latestWithdrawal = Withdrawal::where('user_id', $request->user_id)->latest()->first();
            $old_balance = $latestWithdrawal->old_balance;
        } else {
            $old_balance = 0;
        }

        return response()->json([
            'success' => true,
            'message' => 'user balance based on withdrawal history',
            'balance' => $old_balance
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'request_from' => 'required|string|in:vendor,affiliate',
            'amount' => 'required|numeric|min:0',
            'bank_account' => 'required',
            'bank_name' => 'required|string',
        ]);

        $checkWithdrawHistory = Withdrawal::where('user_id', $request->user_id)->exists();

        if ($checkWithdrawHistory) {
            $latestWithdrawal = Withdrawal::where('user_id', $request->user_id)->latest()->first();
            $old_balance = $latestWithdrawal->old_balance;
        } else {
            if ($request->request_from === 'vendor') {
                $old_balance = Transaction::where('user_id', $request->user_id)->sum('org_vendor');
            } elseif ($request->request_from === 'affiliate') {
                $old_balance = Transaction::where('user_id', $request->user_id)->sum('org_aff');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request source. Must be either vendor or affiliate.'
                ], 400);
            }
        }

        $user = User::findOrFail($request->user_id);
        $user_email = $user->email;

        $requestDetails = Withdrawal::create([
            'user_id' => $request->user_id,
            'email' => $user_email,
            'amount' => $request->amount,
            'bank_account' => $request->bank_account,
            'bank_name' => $request->bank_name,
            'status' => 'pending',
            'old_balance' => $old_balance,
        ]);

        return response()->json([
            'message' => 'Request sent successfully',
            'success' => true,
            'request_details' => $requestDetails
        ]);
    }

    public function salesAffiliate(Request $request)
    {
        $totalNoSales = Sale::where('affiliate_id', $request->affiliate_id)->where('user_id', $request->user_id)->count();


        return response()->json([
            'message' => "affilaite number of sales",
            'success' => true,
            'no of sales' => $totalNoSales
        ]);
    }


    public function totalSaleAff(Request $request)
    {
        $totalNoSales = Sale::where('affiliate_id', $request->affiliate_id)->count();
        $totalCommission = Transaction::where('affiliate_id', $request->affiliate_id)->where('status', 'success')->sum('org_aff');


        return response()->json([
            'message' => "affilaite number of sales",
            'success' => true,
            'no of sales' => $totalNoSales,
            'total commission' => $totalCommission
        ]);
    }
}
