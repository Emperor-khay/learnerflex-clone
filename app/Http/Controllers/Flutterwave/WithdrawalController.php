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
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{

    //was here
    public function index(Request $request)
    {
        $user = auth()->user();

        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1', // Withdrawal amount in Naira
            'type' => 'required|string|in:affiliate,vendor',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Retrieve validated data
            $validatedData = $validator->validated();
            $amountInNaira = $validatedData['amount'];
            $type = $validatedData['type'];

            // Convert withdrawal amount to Kobo for calculations
            $amountInKobo = $amountInNaira * 100;

            // Calculate available balance in Kobo
            if ($type === 'affiliate') {
                $totalWithdrawals = Withdrawal::where('user_id', $user->id)
                    ->where('type', 'affiliate')
                    ->where('status', 'approved')
                    ->sum('amount'); // Already in Kobo

                $totalEarnings = Sale::where('affiliate_id', $user->aff_id)
                    ->where('status', 'success')
                    ->sum('org_aff'); // Earnings in Kobo

                $availableBalance = $totalEarnings - $totalWithdrawals;
            } elseif ($type === 'vendor') {
                $totalWithdrawals = Withdrawal::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('type', 'vendor')
                    ->sum('amount'); // Already in Kobo

                $totalEarnings = Sale::where('vendor_id', $user->id)
                    ->where('status', 'success')
                    ->sum('org_vendor'); // Earnings in Kobo

                $availableBalance = $totalEarnings - $totalWithdrawals;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type provided.',
                ], 400);
            }

            // Check if the user has enough balance
            if ($amountInKobo > $availableBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance to process this withdrawal request.',
                ], 400);
            }

            // Use bank details from the user's profile only
            $bankName = $user->bank_name;
            $bankAccount = $user->bank_account;
            $bankcode = $user->bankcode;

            if (!$bankName || !$bankAccount || !$bankcode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank name, bankcode and account are required in the user profile to proceed with the withdrawal request.',
                ], 400);
            }

            // Create the withdrawal request in Kobo
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'bankcode' => $bankcode,
                'type' => $type,
                'amount' => $amountInKobo, // Store amount in Kobo
                'old_balance' => $availableBalance, // Save the current balance in Kobo
                'bank_name' => $bankName,
                'bank_account' => $bankAccount,
                'status' => 'pending',
            ]);

            if ($withdrawal) {
                try {
                    $name = $user->name;
                    Mail::to($user->email)->send(new \App\Mail\WithdrawalProcessingMail($name, $amountInNaira)); // Send Naira amount in email
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
            Log::error('Error making withdrawal request', ['error' => $th->getMessage()]);

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

    // public function withdrawRecord(Request $request)
    // {
    //     try {
    //         // Validate that the status is a valid filter option (adjust statuses as needed)
    //         $request->validate([
    //             'status' => 'nullable|in:pending,approved,rejected',
    //         ]);

    //         // Get the authenticated user
    //         $user = auth()->user();

    //         // Fetch withdrawals for the authenticated user, filtered by status if provided
    //         $withdrawals = Withdrawal::where('user_id', $user->id)
    //             ->when($request->filled('status'), function ($query) use ($request) {
    //                 return $query->where('status', $request->input('status'));
    //             })
    //             ->orderByDesc('created_at') // Sort in descending order
    //             ->get();

    //         // Convert amounts from Kobo to Naira and return the results
    //         $withdrawals->transform(function ($withdrawal) {
    //             $withdrawal->amount_naira = $withdrawal->amount / 100; // Convert Kobo to Naira
    //             return $withdrawal;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Withdrawal records retrieved successfully.',
    //             'withdrawals' => $withdrawals,
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         // Log the error for debugging purposes (optional)
    //         \Log::error('Error fetching withdrawal records', ['error' => $th]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while fetching the withdrawal records.',
    //             'error' => $th->getMessage(),
    //         ], 500);
    //     }
    // }
}
