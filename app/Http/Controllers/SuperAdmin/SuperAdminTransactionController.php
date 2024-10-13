<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $perPage = $request->input('per_page', 10); // Default to 10 if not provided
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
}
