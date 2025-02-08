<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionStatusController extends Controller
{
    public function checkStatus(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $transaction = Transaction::where('transaction_id', $request->transaction_id)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $baseResponse = [
            'success' => true,
            'transaction_status' => $transaction->status,
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_type' => $transaction->description,
            'error' => $transaction->error,
        ];

        switch ($transaction->description) {
            case 'product_sale':
                return $this->handleProductSale($transaction, $baseResponse);
            case 'signup_fee':
                return $this->handleSignupFee($transaction, $baseResponse);
            case 'market_access':
                return $this->handleMarketAccess($transaction, $baseResponse);
            default:
                Log::error('Unknown transaction type', ['transaction_id' => $transaction->id]);
                return response()->json(array_merge($baseResponse, ['error' => 'Unknown transaction type']), 400);
        }
    }

    private function handleProductSale($transaction, $baseResponse)
    {
        $product = Product::find($transaction->product_id);
        if (!$product) {
            return response()->json(array_merge($baseResponse, ['error' => 'Product not found']), 404);
        }

        $vendor = User::find($product->user_id);
        if (!$vendor) {
            return response()->json(array_merge($baseResponse, ['error' => 'Vendor not found']), 404);
        }

        return response()->json(array_merge($baseResponse, [
            'transaction' => $transaction,
            'product_name' => $product->name,
            'product_access_link' => $product->access_link,
            'product_image' => $product->image,
            'vendor_id' => $product->vendor_id,
            'vendor_user_id' => $product->user_id,
        ]), 200);
    }

    private function handleSignupFee($transaction, $baseResponse)
    {
        if ($transaction->status === 'success') {
            $redirectUrl = 'https://learnerflex.com/auth/signup?status=success&message=' . urlencode('Registration successful! You can now log in.') . '&email=' . urlencode($transaction->email);
            return response()->json(array_merge($baseResponse, [
                'redirect_url' => $redirectUrl,
            ]), 200);
        } else {
            return response()->json($baseResponse, 200);
        }
    }

    private function handleMarketAccess($transaction, $baseResponse)
    {
        if ($transaction->status === 'success') {
            return response()->json(array_merge($baseResponse, [
                'message' => 'Market access unlocked successfully!',
            ]), 200);
        } else {
            return response()->json($baseResponse, 200);
        }
    }
}
