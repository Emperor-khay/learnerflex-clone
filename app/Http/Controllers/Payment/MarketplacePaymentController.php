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
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;

class MarketplacePaymentController extends Controller
{
    

    public function payment(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'email' => 'required|string|email',
            'currency' => 'required|string|in:NGN,USD',
            // Add other necessary validation rules if needed
        ]);

        // Generate a unique order ID for each transaction
        $orderId = strtoupper(Str::random(5) . time());  // Random 10 character string for the order ID

        // Prepare the data for the payment
        $formData = [
            'email' => $request->email,  // Use validated email
            'amount' => 1100 * 100, // Amount in cents (NGN)
            'currency' => $request->currency,
            'callback_url' => route('marketplace.payment.callback') . '?email=' . urlencode($request->email). '&orderId=' . $orderId,
            "orderID" => $orderId,
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
                'currency' => $request->currency,
                'status' => 'pending',
                'org_company' => 0,
                'org_vendor' => 0,
                'org_aff' => 0,
                'tx_ref' => null,
                'transaction_id' => $orderId, // Save the dynamic order ID
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
        // Validate the request input
    $validator = Validator::make($request->all(), [
        'reference' => 'required|string',
        'email' => 'required|email',
        'orderId' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid input data',
            'errors' => $validator->errors(),
        ], 400);
    }

    $email = $request->input('email');
    $reference = $request->input('reference');  // Get reference from the request
    $orderId = $request->input('orderId');  // Get orderId from the request

        $paymentDetails = Paystack::getPaymentData();  // Use Paystack package to get payment details
    
        // Check if payment was successful
        if ($paymentDetails['data']['status'] == "success") {
            
            // Create or update the user record
            $user = User::updateOrCreate(
                ['email' => $email],  // Find user by email
                [
                    'name' => null,
                    'phone' => null,
                    'password' => null,
                    'country' => null,
                    'refferal_id' => 0,
                    'image' => null,
                    'market_access' => 1,
                    'bank_account' => null,
                    'bank_name' => null
                ]
            );
    
            // Update the transaction record
            $transaction = Transaction::where('email', $email)->where('transaction_id', $orderId)->latest()->first();
    
            if ($transaction) {
                $transaction->update([
                    'tx_ref' => $reference,
                    'status' => $paymentDetails['data']['status'],
                    'description' => 'marketplace_unlock'
                ]);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Transaction successful.',
                'user' => $user,
                'status' => $paymentDetails['data']['status']
            ]);
        } else {
            $status = $paymentDetails['data']['status'];
    
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed',
                'status' => $status,
            ]);
        }
    }
    


    // public function redirectToGateway()
    // {
    //     $email = strtolower(Str::random(6)); 
    //     $formData = [
    //         'email' =>  $email . '@mail.com', // User's email
    //         'amount' => 7100 * 100, // Amount in kobo
    //         'currency' => 'NGN', // Currency is NGN (Nigerian Naira)
    //         'callback_url' => route('ohyes'), // Generate callback URL
    //         "orderID" => uniqid('order_') . '_' . time(),
    //     ];
    //     try {
    //         $paymentData =  Paystack::getAuthorizationUrl($formData);
    //         return response()->json([
    //             'success' => true,
    //             'authorization_url' => $paymentData // Return the authorization URL in the response
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'The Paystack token has expired. Please refresh the page and try again.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // /**
    //  * Obtain Paystack payment information
    //  * @return void
    //  */
    // public function handleGatewayCallback()
    // {
    //     $paymentDetails = Paystack::getPaymentData();

    //     return response()->json([
    //         'success' => true,
    //         'message' => $paymentDetails,
    //         'error' => 'error not'
    //     ], 500);
    //     //dd($paymentDetails);
    //     // Now you have the payment details,
    //     // you can store the authorization_code in your db to allow for recurrent subscriptions
    //     // you can then redirect or do whatever you want
    // }
}
