<?php

namespace App\Http\Controllers\Flutterwave;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function initiatePayment(array $data)
    {
        try {
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->post('https://api.flutterwave.com/v3/payments', $data);

            // Handle the response (e.g., redirect user, log response, etc.)
            $responseData = $response->json();
            return response()->json($responseData, $response->status());

        } catch (\Exception $e) {
            return $this->error([], $e->getMessage(), 500);
        }
    }

    public function handleCallback(Request $request)
    {
        if ($request->query('status') === 'successful') {
            // Retrieve the transaction details from your database using the tx_ref
            $transaction = Transaction::where('tx_ref', $request->query('tx_ref'))->first();

            if ($transaction) {
                // Verify the payment with Flutterwave
                $response = Http::withToken(env('FLW_SECRET_KEY'))
                    ->get('https://api.flutterwave.com/v3/transactions/' . $request->query('transaction_id') . '/verify');

                $responseBody = $response->json();
                $tx_ref = $request->query('tx_ref');
                $clientUrl = env('CLIENT_URL');

                if (
                    $responseBody['data']['status'] === 'successful' &&
                    $responseBody['data']['amount'] == $transaction->amount &&
                    $responseBody['data']['currency'] === 'NGN'
                ) {
                    // Success! Confirm the customer's payment
                    // Update the transaction status to 'successful'
                    $transaction->transaction_id = $request->query('transaction_id');
                    $transaction->status = 'successful';
                    $transaction->save();

                    return redirect("$clientUrl/auth/payment?tx_ref=$tx_ref&message=Payment+confirmed+successfully.");
                } else {
                    // Payment verification failed
                    $transaction->status = 'failed';
                    $transaction->save();

                    return redirect("$clientUrl/auth/payment?tx_ref=$tx_ref&message=Payment+verification+failed.");
                }
            } else {
                return $this->error([], 'Transaction not found.', 404);
            }
        }

        // If payment wasn't successful or if some other error occurred
        return $this->error([], 'Payment was unsuccessful.', 400);
    }
}
