<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;

class SuperAdminTransactionController extends Controller
{
    // View all transactions
    public function index()
    {
        $transactions = Transaction::all();
        return response()->json($transactions);
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
