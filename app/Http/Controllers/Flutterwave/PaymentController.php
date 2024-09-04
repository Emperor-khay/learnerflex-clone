<?php

namespace App\Http\Controllers\Flutterwave;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Service\UserService;
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

    public function handleOnboardCallback(Request $request)
    {
        $status = $request->query('status');
        $clientUrl = env('CLIENT_URL');
        $tx_ref = $request->query('tx_ref');
        if ($status === 'successful') {
            // Retrieve the transaction details from your database using the tx_ref
            $transaction = Transaction::where('tx_ref', $tx_ref)->first();

            if ($transaction) {
                // Verify the payment with Flutterwave
                $response = Http::withToken(env('FLW_SECRET_KEY'))
                    ->get('https://api.flutterwave.com/v3/transactions/' . $request->query('transaction_id') . '/verify');

                $responseBody = $response->json();

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

                    // update user access to market after signup fee paid
                    $transaction->user()->update([
                        'market_access' => true
                    ]);

                    return redirect("$clientUrl/auth/payment?tx_ref=$tx_ref&message=Payment+confirmed+successfully.");
                } else {
                    // Payment verification failed
                    $transaction->status = 'failed';
                    $transaction->save();

                    return redirect("$clientUrl/auth/payment?status=$status&tx_ref=$tx_ref&message=Payment+verification+failed.");
                }
            } else {
                return redirect("$clientUrl/auth/payment?message=Transaction+not+found&status=$status&tx_ref=$tx_ref");
            }
        }

        // If payment wasn't successful or if some other error occurred
        return redirect("$clientUrl/auth/payment?message=Payment+verification+failed&status=$status&tx_ref=$tx_ref");
    }

    public function handleMarketAccessCallback(Request $request)
    {
        $status = $request->query('status');
        $clientUrl = env('CLIENT_URL');
        $tx_ref = $request->query('tx_ref');
        if ($status === 'successful') {
            // Retrieve the transaction details from your database using the tx_ref
            $transaction = Transaction::where('tx_ref', $tx_ref)->first();

            if ($transaction) {
                // Verify the payment with Flutterwave
                $response = Http::withToken(env('FLW_SECRET_KEY'))
                    ->get('https://api.flutterwave.com/v3/transactions/' . $request->query('transaction_id') . '/verify');

                $responseBody = $response->json();

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

                    // update affiliate access to market products after payment
                    $transaction->user()->update([
                        'market_access' => true
                    ]);

                    return redirect("$clientUrl/auth/payment?tx_ref=$tx_ref&message=Payment+confirmed+successfully.");
                } else {
                    // Payment verification failed
                    $transaction->status = 'failed';
                    $transaction->save();

                    return redirect("$clientUrl/auth/payment?status=$status&tx_ref=$tx_ref&message=Payment+verification+failed.");
                }
            } else {
                return redirect("$clientUrl/auth/payment?message=Transaction+not+found&status=$status&tx_ref=$tx_ref");
            }
        }

        // If payment wasn't successful or if some other error occurred
        return redirect("$clientUrl/auth/payment?message=Payment+verification+failed&status=$status&tx_ref=$tx_ref");

    }

    public function handleCheckAccount(Request $request)
    {
        try {
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->post('https://api.flutterwave.com/v3/accounts/resolve', [
                    'account_number' => $request->number,
                    'account_bank' => $request->country,
                ]);

            $responseBody = $response->json();

            if (
                $responseBody['status'] === 'success'
            ) {
                // Success! Update the user account
                $user = $request->user();
                $account = new UserService();
                $account->createAccountForUser($user, [
                    'name' => $request->name,
                    'number' => $request->number,
                    'country' => $request->country,
                ]);

                return $this->success(
                    [
                        'name' => $responseBody['data']['account_name'],
                    ],
                    'Bank info updated!'
                );
            } else {
                // Payment verification failed
                return $this->error(null, 'Unable to verify account!', 400);
            }

        } catch (\Throwable $th) {
            //throw $th;
            return $this->error(null, $th->getMessage(), 400);
        }
    }
}
