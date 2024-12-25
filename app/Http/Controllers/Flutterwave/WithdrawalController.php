<?php

namespace App\Http\Controllers\Flutterwave;

use App\Models\Sale;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Service\WithdrawalService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class WithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Request for latest withdrawals
     */
    //     public function index(Request $request)
    // {
    //     try {
    //         $user = auth()->user();

    //         // Validate the request to ensure 'amount' is present and numeric
    //         $validatedData = $request->validate([
    //             'amount' => 'required|numeric|min:1',
    //         ]);

    //         // Retrieve the validated 'amount'
    //         $amount = $validatedData['amount'];

    //         // Use bank details from the user's profile only
    //         $bankName = $user->bank_name;
    //         $bankAccount = $user->bank_account;

    //         // If bank details are missing in the user's profile, return an error
    //         if (!$bankName || !$bankAccount) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Bank name and account are required in the user profile to proceed with the withdrawal request.',
    //             ], 400);
    //         }

    //         // Create the withdrawal request
    //         $withdrawal = Withdrawal::create([
    //             'user_id' => $user->id,
    //             'email' => $user->email,
    //             'amount' => $amount,
    //             'old_balance' => null, 
    //             'bank_name' => $bankName,
    //             'bank_account' => $bankAccount,
    //             'status' => 'pending',
    //         ]);

    //         if ($withdrawal) {
    //             try {
    //                 $name = $user->name;
    //                 Mail::to($user->email)->send(new \App\Mail\WithdrawalProcessingMail($name, $amount));
    //             } catch (\Exception $e) {
    //                 Log::error('Error sending mail', ['error' => $e->getMessage()]);
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Withdrawal request submitted successfully.',
    //             'withdrawal' => $withdrawal,
    //         ], 201);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while submitting the withdrawal request.',
    //             'error' => $th->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate the request to ensure 'amount' is present and numeric
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);

            // Retrieve the validated 'amount'
            $amount = $validatedData['amount'];

            // Calculate the user's available balance (Example for affiliate type)
            $totalWithdrawals = Withdrawal::where('user_id', $user->id)
                ->where('status', 'approved')
                ->sum('amount');

            $totalEarnings = Sale::where('affiliate_id', $user->aff_id)
                ->where('status', 'success')
                ->sum('org_aff');

            $availableBalance = $totalEarnings - $totalWithdrawals;

            // Check if the user has enough balance
            if ($amount > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance to process this withdrawal request.',
                ], 400);
            }

            // Use bank details from the user's profile only
            $bankName = $user->bank_name;
            $bankAccount = $user->bank_account;

            // If bank details are missing in the user's profile, return an error
            if (!$bankName || !$bankAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank name and account are required in the user profile to proceed with the withdrawal request.',
                ], 400);
            }

            // Create the withdrawal request
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'amount' => $amount,
                'old_balance' => $availableBalance, // Save the current balance
                'bank_name' => $bankName,
                'bank_account' => $bankAccount,
                'status' => 'pending',
            ]);

            if ($withdrawal) {
                try {
                    $name = $user->name;
                    Mail::to($user->email)->send(new \App\Mail\WithdrawalProcessingMail($name, $amount));
                } catch (\Exception $e) {
                    Log::error('Error sending mail', ['error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully.',
                'withdrawal' => $withdrawal,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the withdrawal request.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Request for total sum of withdrawals made by user
     */
    public function WithdrawRecord(Request $request)
    {
        try {
            // Validate that the status is a valid filter option
            $request->validate([
                'status' => 'in:pending,approved,rejected' // Adjust statuses according to your application
            ]);

            // Get the authenticated user
            $user = auth()->user();

            // Fetch all withdrawals by the user, filtered by the status if provided
            $withdrawals = Withdrawal::where('user_id', $user->id)
                ->when($request->has('status'), function ($query) use ($request) {
                    return $query->where('status', $request->input('status'));
                })
                ->orderBy('created_at', 'desc') // Sort in descending order
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal records retrieved successfully.',
                'withdrawals' => $withdrawals,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the withdrawal records.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
