<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Models\Transaction; 
use App\Models\Sale; 
use App\Models\User; 
use App\Models\Product; 
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PaystackController extends Controller
{
    
    public function make_payment(Request $request)
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
            'callback_url' => 'https://learnerflex.com/dashboard/product/pay?p_id=' . request('product_id') . '&u_id=' . request('user_id') . '&aff_id=' . request('aff_id') . '&status=', 
            'user_id' => request('user_id'), 
            'aff_id' => request('aff_id'),    
            'amount' => $amountKobo,
            'product_id' => request('product_id'), 
        ];
        

        // Retrieve Affiliate ID

        if($request->has('aff_id')){
            $getAffiliateId = User::where('aff_id', request('aff_id'))->first();
            $affiliate_id = $getAffiliateId ? $getAffiliateId->id : 0;
        }
        

        // Calculate shares
        $org_company_share = $amount * 0.05; // 5% to company
        $org_vendor_share = $amount * 0.45;  // 45% to vendor
        $org_aff_share = $amount * 0.50;     // 50% to affiliate

        
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
                    'amount' => $amount, 
                    'currency' => request('currency'),
                    'status' => 'pending',
                    'org_company' => $org_company_share,
                    'org_vendor' => $org_vendor_share,
                    'org_aff' => $org_aff_share,
                    'is_onboard' => 0,
                    'tx_ref' => null
                ]);

                // Return the authorization URL in the JSON response
                return response()->json([
                    'success' => true,
                    'authorization_url' => $pay->data->authorization_url
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Somethingssss went wrong with the payment initialization."
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
             Log::error('Curl error: ' . curl_error($ch));
            return false;
        }


        curl_close($ch);


        return $result;
    }

    public function payment_callback()
    {
        $reference = request('reference');
        $response = json_decode($this->verify_payment($reference));
        $email = request('email');

        if ($response && $response->status == "success") {

            $transaction = Transaction::where('email', $email)->latest()->first();
            $transactionAffId = $transaction->affiliate_id;
            $transactionProductId = $transaction->product_id;
            $transactionUserId = $transaction->user_id;
            $transactionAmount = $transaction->amount;

            $refferer = User::find($transactionAffId);
            

            $aff_id = $refferer->aff_id;
            
      
            if($transaction) {
             $transaction->update([
                 'tx_ref' => request('reference'),
                 'status' => $response->status,
                 'is_onboard' => 1,
             ]);
             }
             

            Sale::create([
                'affiliate_id' => $transactionAffId,
                'product_id' => $transactionProductId, // Get product ID from request
                'user_id' => $transactionUserId, // Get user ID from request
                'transaction_id' => $reference,
                'amount' => $transactionAmount / 100,
            ]);
            
            
            $product = Product::find($transactionProductId);
            $product_name = $product->name;
            $product_access_link = $product->access_link;

            $checkUser = User::where('email', request('email'))->first();

            if (!$checkUser) {
                Mail::to(request('email'))->send(new \App\Mail\RegisterLink($aff_id));  
            }
            
            return response()->json([
                    'success' => true,
                    'status' => $response->status,
                    'transaction'=> $transaction,
                    'product_name'=> $product_name,
                    'product_access_link'=> $product_access_link,
                    
            ], 200);

            
        } else {
            return response()->json(['message' => 'transaction not successful', 'success' => false]);
        }
    }

    public function verify_payment($reference)
    {
        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
            "Cache-Control: no-cache"
        ));

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}

