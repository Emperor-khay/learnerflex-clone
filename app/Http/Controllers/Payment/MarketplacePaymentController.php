<?php

namespace App\Http\Controllers\Payment;

use App\Models\User; 
use App\Models\Product; 
use Illuminate\Support\Str;
use App\Models\Transaction; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class MarketplacePaymentController extends Controller
{

    public function make_payment()
    {
        // User must be logged in, and we get their email from the authenticated user
        $user = auth()->user();
        $email = $user->email;

        // Amount to be paid (in NGN kobo)
        $amount = 1000; // Amount in NGN
        $amountKobo = $amount * 100; // Convert NGN to kobo (1 NGN = 100 kobo)

        // Prepare the data for the payment
        $formData = [
            'email' => $email, // User's email
            'amount' => $amountKobo, // Amount in kobo
            'currency' => 'NGN', // Currency is NGN (Nigerian Naira)
            'callback_url' => route('payment.callback'), // Generate callback URL
        ];

        // Initialize payment with Paystack
        $pay = json_decode($this->initialize_payment($formData));

        if ($pay && $pay->status) {
            // Payment initialization successful

            // Create a new transaction record
            Transaction::create([
                'user_id' => $user->id, // Use the authenticated user's ID
                'email' => $email, // User's email
                'affiliate_id' => 0,
                'product_id' => 0,
                'amount' => $amount, // Amount in NGN
                'currency' => 'NGN',
                'status' => 'pending', // Set transaction as pending
                'org_company' => 0,
                'org_vendor' => 0,
                'org_aff' => 0,
                'is_onboard' => 0,
                'tx_ref' => null,
            ]);

            // Return the authorization URL in the JSON response
            return response()->json([
                'success' => true,
                'authorization_url' => $pay->data->authorization_url
            ], 200);
        } else {
            // Payment initialization failed
            Log::error('Paystack payment initialization failed: ' . json_encode($pay));
            return response()->json([
                'success' => false,
                'message' => "Something went wrong with the payment initialization."
            ], 401);
        }
    }

    public function payment_callback(Request $request)
    {
        // Verify the payment using the provided reference
        $reference = $request->input('reference');

        if (!$reference) {
            return response()->json(['success' => false, 'message' => 'No payment reference provided'], 400);
        }

        // Verify the payment with Paystack
        $response = json_decode($this->verify_payment($reference));

        if ($response && $response->status == "success") {
            // Payment was successful
            $user = auth()->user(); // Get the logged-in user

            // Ensure that the transaction exists and is updated
            $transaction = Transaction::where('email', $user->email)->latest()->first();

            if ($transaction) {
                $transaction->update([
                    'tx_ref' => $reference,
                    'status' => 'success',
                    'is_onboard' => 1, // User has paid
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction successful. User has access to the marketplace.',
                'user' => $user,
                'status' => $response->status
            ]);
        } else {
            // Payment failed or was incomplete
            $status = $response->status ?? 'failed';
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed or incomplete',
                'status' => $status
            ]);
        }
    }

    private function initialize_payment($formData)
    {
        // Paystack API initialization URL
        $url = "https://api.paystack.co/transaction/initialize";
        $fields_string = http_build_query($formData);

        // cURL request for payment initialization
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
            "Cache-Control: no-cache"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL and return the result
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function verify_payment($reference)
    {
        // Paystack API verification URL
        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

        // cURL request to verify payment
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
            "Cache-Control: no-cache"
        ]);

        // Execute cURL and return the result
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
    //
    // public function make_payment()
    // {
    //     $amount = 1000;
    //     $amountKobo = $amount * 100;
    //     // Prepare the data for the payment
    //     $formData = [
    //         'email' => request('email'),
    //         'amount' => 50 * 100, 
    //         'currency' => request('currency'),
    //         'callback_url' => "https://learnerflex.com/auth/signup?otp=sggd63vx7td3dydg3", 
    //     ];

 
    //     // Initialize payment with Paystack
    //     $pay = json_decode($this->initialize_payment($formData));
    
    //     if ($pay) {
    //         // Check if payment initialization was successful
    //         if ($pay->status) {

    //             Transaction::create([
    //                 'user_id' => 0, 
    //                 'email' => request('email'),
    //                 'affiliate_id' => 0,
    //                 'product_id' => 0,
    //                 'amount' => $amount,
    //                 'currency' => request('currency'),
    //                 'status' => 'pending',
    //                 'org_company' => 0,
    //                 'org_vendor' => 0,
    //                 'org_aff' => 0,
    //                 'is_onboard' => 0,
    //                 'tx_ref' => null,
    //             ]);

    //             // Return the authorization URL in the JSON response
    //             return response()->json([
    //                 'success' => true,
    //                 'authorization_url' => $pay->data->authorization_url
    //             ], 200);

    //         } else {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Something went wrong with the payment initialization."
    //             ], 401);
    //         }
    //     } else {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Something went wrong with the payment initialization."
    //         ], 401);
    //     }
    // }

    // public function initialize_payment($formData)
    // {
    //     $url = "https://api.paystack.co/transaction/initialize";
    //     $fields_string = http_build_query($formData);

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
    //         "Cache-Control: no-cache"
    //     ));
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     $result = curl_exec($ch);

    //     return $result;
    // }

    // public function payment_callback()
    // {
    //     $reference = request('reference');
    //     $response = json_decode($this->verify_payment($reference));
        
    //     do {
    //         $aff_id = Str::random(20);
    //         $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //     } while ($exists);
            
    //     if ($response && $response->status == "success") {

    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => null,
    //             'email' => request('email'),
    //             'phone' => null,
    //             'password' => null,
    //             'country' => null,
    //             'refferal_id' => 0,
    //             'image' => null,
    //             'has_paid_onboard' => 1,
    //             'is_vendor' => 0,
    //             'vendor_status' => 'down',
    //             'otp' => 'sggd63vx7td3dydg3',
    //             'market_access' => 1,
    //             'bank_account' => null,
    //             'bank_name' => null
    //         ]);


    //         $transaction = Transaction::where('email', request('email'))->latest()->first();

    //         if ($transaction) {
    //             $transaction->update([
    //                 'tx_ref' => request('reference'),
    //                 'status' => $response->status,
    //                 'is_onboard' => 1,
    //             ]);
    //         }
                
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'transaction successful. User has been recorded in database with otp',
    //             'user' => $user,
    //             'status' => $response->status
    //         ]);
            
    //     } else {

    //         $status = $response->status == "pending" ? 'pending' : 'failed';

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'transaction not successful',
    //             'status' => $status,
    //         ]);
    //     }
    // }

    // public function verify_payment($reference)
    // {
    //     $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //         "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
    //         "Cache-Control: no-cache"
    //     ));

    //     $result = curl_exec($ch);


    //     curl_close($ch);

    //     return $result;
    // }
}




