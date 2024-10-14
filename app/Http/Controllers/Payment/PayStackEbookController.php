<?php

namespace App\Http\Controllers\Payment;

use Exception;
use App\Models\Sale; 
use App\Models\User; 
use App\Models\Product; 
use App\Models\Transaction; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\EbookPaymentRequest;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackEbookController extends Controller
{
    
    public function make_payment(EbookPaymentRequest $request)
    {
        // Retrieve the product from the request
        $product = Product::find(request('product_id'));
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }
        
        // Calculate the amount (Paystack expects amount in kobo or lowest currency unit)
        $amount = $product->price;
        $amountKobo = $product->price * 100;
    
    
        // Prepare the data for the payment
        $formData = [
            'email' => request('email'),
            'currency' => request('currency'),
            'callback_url' => request('callback_url'), 
            'user_id' => request('user_id'), 
            'aff_id' => request('aff_id'),    
            'amount' => $amountKobo,
            'metadata' => [
            'product_id' => $request->product_id, // Include product ID in metadata
        ]
        ];
        

        // Retrieve Affiliate ID

        if($request->has('aff_id')){
            $getAffiliateId = User::where('aff_id', request('aff_id'))->first();
            $affiliate_id = $getAffiliateId ? $getAffiliateId->id : 0;
        }
        

        
          // Fetch the product's affiliate commission percentage
    $aff_commission_percentage = $product->commission ? $product->commission / 100 : 0; // Default to 35% if not set

    // Calculate shares
    $org_company_share = $amount * 0.05; // 5% to company (admin)
    $org_aff_share = $amount * $aff_commission_percentage;  // Dynamic affiliate share
    $org_vendor_share = $amount - ($org_company_share + $org_aff_share);  // Vendor gets the rest

        
        // Initialize payment with Paystack
         $pay = json_decode($this->initialize_payment($formData));
         
    
        if ($pay) {
            // Check if payment initialization was successful
            if ($pay->status) {
                
                Transaction::create([
                    'user_id' => request('user_id'), // Get user ID from request
                    'email' => request('email'),
                    'affiliate_id' =>  $affiliate_id ?? null,
                    'product_id' => request('product_id'), 
                    'amount' => $amount, // amount is in kobo
                    'currency' => request('currency'),
                    'status' => 'pending',
                    'org_company' => $org_company_share,
                    'org_vendor' => $org_vendor_share,
                    'org_aff' => $org_aff_share,
                    'is_onboard' => 0,
                    'tx_ref' => $pay->data->reference,
                ]);

                // Return the authorization URL in the JSON response
                return response()->json([
                    'success' => true,
                    'authorization_url' => $pay->data->authorization_url
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Something went wrong with the payment initialization."
                ], 401);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong with the payment initialization."
            ], 401);
            
        }
    }

    public function initialize_payment($formData)
    {
        $url = "https://api.paystack.co/transaction/initialize";
        $fields_string = http_build_query($formData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
            "Cache-Control: no-cache"
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }


        curl_close($ch);


        return $result;
    }


    public function paymentCallback()
{
    // Get the reference from the request
    $reference = request('reference');

    // Verify the payment with Paystack
    $response = Paystack::getPaymentData();

    if ($response['status'] && $response['data']['status'] === 'success') {

        // Get the transaction details from Paystack
        $paystackAmount = $response['data']['amount']; // Paystack returns amount in kobo (100 kobo = 1 Naira)
        $reference = $response['data']['reference'];
        $product_id = $response['data']['metadata']['product_id']; // Assuming product ID is passed in metadata

        // Find the product
        $product = Product::find($product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'success' => false], 404);
        }

        // Get the product price in Kobo
        $expectedAmount = $product->price * 100;

        // Check if the amount paid matches the product price
        if ($paystackAmount != $expectedAmount) {
            return response()->json(['message' => 'Payment amount does not match product price', 'success' => false], 400);
        }

        // Find the transaction by reference
        $transaction = Transaction::where('tx_ref', $reference)->first();

        if ($transaction) {
            // Update the transaction status and onboard status
            $transaction->update([
                'status' => 'success',
                'is_onboard' => 1,
            ]);

            // Create a Sale record
            Sale::create([
                'affiliate_id' => $transaction->affiliate_id,
                'product_id' => $transaction->product_id,
                'user_id' => $transaction->user_id,
                'transaction_id' => $reference,
                'amount' => $transaction->amount, // Store amount in Naira
            ]);

            // Send user an email if they are new
            $checkUser = User::where('email', $response['data']['customer']['email'])->first();
            if (!$checkUser) {
                Mail::to($response['data']['customer']['email'])->send(new \App\Mail\RegisterLink($transaction->affiliate_id));
            }

            return response()->json(['message' => 'Transaction successful', 'success' => true], 200);
        }

        return response()->json(['message' => 'Transaction not found', 'success' => false], 404);
    }

    return response()->json(['message' => 'Transaction verification failed', 'success' => false], 400);
}


    // public function paymentCallback()
    // {
    //     $reference = request('reference');
    //     $response = json_decode($this->verify_payment($reference));
    //     $email = request('email');

    //     if ($response && $response->status == "success") {

    //         $transaction = Transaction::where('email', $email)->latest()->first();
    //         $transactionAffId = $transaction->aff_id;
    //         $transactionProductId = $transaction->product_id;
    //         $transactionUserId = $transaction->user_id;
    //         $transactionAmount = $transaction->amount;

    //         $refferer = User::find($transactionAffId);

    //         $aff_id = $refferer->aff_id;
      
    //         if($transaction) {
    //          $transaction->update([
    //              'tx_ref' => request('reference'),
    //              'status' => $response->status,
    //              'is_onboard' => 1,
    //          ]);
    //          }

    //         Sale::create([
    //             'affiliate_id' => $transactionAffId,
    //             'product_id' => $transactionProductId, // Get product ID from request
    //             'user_id' => $transactionUserId, // Get user ID from request
    //             'transaction_id' => $reference,
    //             'amount' => $transactionAmount / 100,
    //         ]);

    //         $checkUser = User::where('email', request('email'))->first();

    //         if (!$checkUser) {
    //             Mail::to($response->data->customer->email)->send(new \App\Mail\RegisterLink($aff_id));  
    //         }

            
    //     } else {
    //         return response()->json(['message' => 'transaction not successful', 'success' => false]);
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
