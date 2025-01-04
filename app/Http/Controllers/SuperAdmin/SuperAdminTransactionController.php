<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Mail\WithdrawalApproved;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;

class SuperAdminTransactionController extends Controller
{
    // View all transactions
    public function index(Request $request)
    {
        // Start a query to fetch transactions
        $query = Transaction::query();

        // Apply filters if provided in the query parameters
        if ($request->filled('tx_ref')) {
            $query->where('tx_ref', $request->input('tx_ref'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('email')) {
            $query->where('email', $request->input('email'));
        }

        // Sort by latest transactions (by created_at or updated_at)
        $query->orderBy('created_at', 'desc');

        // Paginate the results (default 10 per page or any per_page you provide in the query)
        $perPage = $request->input('per_page', 20); // Default to 10 if not provided
        $transactions = $query->paginate($perPage);

        // Return the data in a simplified format
        return response()->json([
            'data' => $transactions->items(), // Paginated data
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ],
        ]);
    }



    // Fetch single transaction by ID
    public function show($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($transaction);
    }


    public function downloadPendingWithdrawals(Request $request)
    {
        try {
            // Fetch pending withdrawal records
            $withdrawals = \App\Models\Withdrawal::with('user')->where('status', 'pending')->get();

            // Define CSV headers
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="pending_withdrawals.csv"',
            ];

            // Create a callback to write the CSV data
            $callback = function () use ($withdrawals) {
                $file = fopen('php://output', 'w');

                // Write the header row
                fputcsv($file, ['BANK CODE', 'BANK', 'ACCOUNT', 'NAME', 'AMOUNT']);

                // Write each withdrawal record
                foreach ($withdrawals as $withdrawal) {
                    $user = $withdrawal->user; // Access the related user
                    $amountInNaira = ($withdrawal->amount / 100) - 50; // Convert to Naira and deduct 50 Naira
                    $amountInNaira = max(0, $amountInNaira); // Ensure non-negative amount

                    fputcsv($file, [
                        $user->bankcode ?? 'N/A',          // BANK CODE (from user model, default to N/A if not set)
                        $withdrawal->bank_name,           // BANK
                        $withdrawal->bank_account,        // ACCOUNT
                        $user->name ?? 'Unknown',         // NAME (from user model, default to Unknown if not set)
                        number_format($amountInNaira, 2)  // AMOUNT in Naira, formatted as 2 decimal places
                    ]);
                }

                fclose($file);
            };

            // Return CSV as a streamed response
            return Response::stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate CSV: ' . $e->getMessage()], 500);
            \Log::error('Failed to generate CSV', ['error' => $e->getMessage()]);
        }
    }



    public function approveAllPendingWithdrawals(Request $request)
    {
        $type = $request->input('type');

        if (!in_array($type, ['affiliate', 'vendor'])) {
            return response()->json([
                'error' => 'Invalid type. Please specify either "affiliate" or "vendor".'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Fetch all pending withdrawal requests for the given type
            $pendingWithdrawals = \App\Models\Withdrawal::where('status', 'pending')
                ->where('type', $type)
                ->get();

            if ($pendingWithdrawals->isEmpty()) {
                return response()->json([
                    'message' => 'No pending withdrawal requests to approve for the specified type.',
                ], 200);
            }

            foreach ($pendingWithdrawals as $withdrawal) {
                try {
                    $user = $withdrawal->user;
                    // Convert amount from kobo to naira
                    $withdrawalAmountInNaira = $withdrawal->amount / 100;
                    // Update withdrawal status to approved
                    $withdrawal->update(['status' => 'approved']);

                    // Send email notification to the user
                    Mail::to($withdrawal->email)->send(new WithdrawalApproved($withdrawalAmountInNaira, $type, $user));
                } catch (\Exception $mailException) {
                    // Log email errors and continue processing
                    \Log::error("Failed to send email for withdrawal ID {$withdrawal->id}: " . $mailException->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'All pending withdrawal requests have been approved, and users notified.',
                'type' => $type,
                'total_approved' => $pendingWithdrawals->count(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to approve withdrawals: ' . $e->getMessage()
            ], 500);
        }
    }
}
