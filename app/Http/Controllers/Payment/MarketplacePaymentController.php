<?php

namespace App\Http\Controllers\Payment;

use Log;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Facades\Paystack;

class MarketplacePaymentController extends Controller
{


    public function payment(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|string|email',
            // Add other necessary validation rules if needed
        ]);

        // Generate a unique order ID for each transaction
        $orderID = strtoupper(Str::random(10));  // Random 10 character string for the order ID

        // Prepare the data for the payment
        $formData = [
            'email' => $request->email,  // Use validated email
            'amount' => 5100 * 100, // Amount in cents (NGN)
            'currency' => 'NGN',
            'callback_url' => route('marketplace.payment.callback') . '?email=' . urlencode($request->email),
            "orderID" => $orderID,
        ];

        try {
            // Initialize payment with Paystack using Unicodeveloper package
            $paymentData = Paystack::getAuthorizationUrl($formData);

            // Store transaction in DB
            Transaction::create([
                'user_id' => 0,
                'email' =>  $request->email,
                'affiliate_id' => 0,
                'product_id' => 0,
                'amount' => $formData['amount'],
                'currency' => $formData['currency'],
                'status' => 'pending',
                'org_company' => 0,
                'org_vendor' => 0,
                'org_aff' => 0,
                'is_onboard' => 0,
                'tx_ref' => null,
                'transaction_id' => $orderID, // Save the dynamic order ID
            ]);

            // Return the authorization URL in the JSON response
            return response()->json([
                'success' => true,
                'authorization_url' => $paymentData, // Authorization URL
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Payment Initialization Error: ' . $e->getMessage());
            // Handle exception
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function payment_callback(Request $request)
    {
        $email = $request->get('email');
        $reference = request('reference');  // Get reference from the callback
        $paymentDetails = Paystack::getPaymentData();  // Use Paystack package to get payment details

        // Check if payment was successful
        if ($paymentDetails['data']['status'] == "success") {

            // Create the user record
            $user = User::create([
                'name' => null,
                'email' => $email,
                'phone' => null,
                'password' => null,
                'country' => null,
                'refferal_id' => 0,
                'image' => null,
                'has_paid_onboard' => 1,
                'is_vendor' => 0,
                'vendor_status' => 'down',
                'otp' => $reference,  // Store the dynamic OTP
                'market_access' => 1,
                'bank_account' => null,
                'bank_name' => null
            ]);

            // Update the transaction record
            $transaction = Transaction::where('email', request('email'))->latest()->first();

            if ($transaction) {
                $transaction->update([
                    'tx_ref' => request('reference'),
                    'status' => $paymentDetails['data']['status'],
                    'is_onboard' => 1,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction successful. User recorded with OTP.',
                'user' => $user,
                'status' => $paymentDetails['data']['status']
            ]);
        } else {
            $status = $paymentDetails['data']['status'] == "pending" ? 'pending' : 'failed';

            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'status' => $status,
            ]);
        }
    }


    public function redirectToGateway()
    {
        $formData = [
            'email' => 'user@mail.com', // User's email
            'amount' => 7100 * 100, // Amount in kobo
            'currency' => 'NGN', // Currency is NGN (Nigerian Naira)
            'callback_url' => route('ohyes'), // Generate callback URL
            "orderID" => 215387,
        ];
        try {
            $paymentData =  Paystack::getAuthorizationUrl($formData)->redirectNow();
            return response()->json([
                'success' => true,
                'authorization_url' => $paymentData // Return the authorization URL in the response
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'The Paystack token has expired. Please refresh the page and try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function handleGatewayCallback()
    {
        $paymentDetails = Paystack::getPaymentData();

        return response()->json([
            'success' => false,
            'message' => $paymentDetails,
            'error' => 'error not'
        ], 500);
        //dd($paymentDetails);
        // Now you have the payment details,
        // you can store the authorization_code in your db to allow for recurrent subscriptions
        // you can then redirect or do whatever you want
    }
}
