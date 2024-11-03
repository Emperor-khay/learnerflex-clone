<?php

namespace App\Http\Controllers\Payment;

use Exception;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackController extends Controller
{

    //     public function make_payment(Request $request)
    // {
    //     try {
    //         // Retrieve the product from the request
    //         $product = Product::find($request->input('product_id'));

    //         if (!$product) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Product not found.'
    //             ], 404);
    //         }

    //         // Calculate the amount (Paystack expects amount in kobo or lowest currency unit)
    //         $amount = $product->price;
    //         $amountKobo = $amount * 100;

    //         // Prepare the data for the payment
    //         $formData = [
    //             'email' => $request->input('email'),
    //             'currency' => $request->input('currency'),
    //             'callback_url' => 'https://learnerflex.com/dashboard/product/pay?p_id=' . $request->input('product_id') . '&u_id=' . $request->input('user_id') . '&aff_id=' . $request->input('aff_id') . '&status=',
    //             'user_id' => $request->input('user_id'),
    //             'aff_id' => $request->input('aff_id'),
    //             'amount' => $amountKobo,
    //             'product_id' => $request->input('product_id'),
    //         ];

    //         // Attempt to retrieve Affiliate ID, if provided
    //         $affiliate_id = null;
    //         if ($request->has('aff_id')) {
    //             $affiliate_id = User::where('aff_id', $request->input('aff_id'))->value('id');
    //             if (!$affiliate_id) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Affiliate not found.'
    //                 ], 404);
    //             }
    //         }

    //         // Fetch the product's affiliate commission percentage
    //         $aff_commission_percentage = $product->commission ? $product->commission / 100 : 0;

    //         // Calculate shares
    //         $org_company_share = $amount * 0.05; // 5% to company (admin)
    //         $org_aff_share = $amount * $aff_commission_percentage;  // Dynamic affiliate share
    //         $org_vendor_share = $amount - ($org_company_share + $org_aff_share);  // Vendor gets the rest

    //         // Initialize payment with Paystack
    //         $pay = json_decode($this->initialize_payment($formData));

    //         // Check if payment initialization was successful
    //         if ($pay && $pay->status) {
    //             // Save transaction data
    //             Transaction::create([
    //                 'user_id' => $request->input('user_id'),
    //                 'email' => $request->input('email'),
    //                 'affiliate_id' => $affiliate_id,
    //                 'product_id' => $request->input('product_id'),
    //                 'amount' => $amount,
    //                 'currency' => $request->input('currency'),
    //                 'status' => 'pending',
    //                 'org_company' => $org_company_share,
    //                 'org_vendor' => $org_vendor_share,
    //                 'org_aff' => $org_aff_share,
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

    //     } catch (\Exception $e) {
    //         // Handle any exceptions that occur during the process
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while processing the payment.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function make_payment(Request $request)
    {
        try {
            // Retrieve the product from the request
            $product = Product::find($request->input('product_id'));

            if (!$product) {
                Log::warning('Product not found', ['product_id' => $request->input('product_id')]);
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found.'
                ], 404);
            }

            // Calculate the amount (Paystack expects amount in kobo or lowest currency unit)
            $amount = $product->price;
            $amountKobo = $amount * 100;
            $orderId = strtoupper(Str::random(5) . time());

            // Prepare the data for the payment
            $formData = [
                'email' => $request->input('email'),
                'currency' => $request->input('currency'),
                'callback_url' => 'https://learnerflex.com/dashboard/product/pay' . '?p_id=' . $request->input('product_id') . '&aff_id=' . $request->input('aff_id') . '&email=' . urlencode($request->input('email')) . '&orderId=' . urlencode($orderId),
                'user_id' => $request->input('user_id'),
                'aff_id' => $request->input('aff_id'),
                'amount' => $amountKobo,
                'product_id' => $request->input('product_id'),
                'orderId' => $orderId,
            ];

            // Attempt to retrieve Affiliate ID, if provided
            $affiliate_id = null;
            if ($request->has('aff_id')) {
                $affiliate_id = User::where('aff_id', $request->input('aff_id'))->value('aff_id');
                if (!$affiliate_id) {
                    Log::info('Affiliate not found', ['aff_id' => $request->input('aff_id')]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Affiliate not found.'
                    ], 404);
                }
            }

            // Fetch the product's affiliate commission percentage
            $aff_commission_percentage = $product->commission ? $product->commission / 100 : 0;

            // Calculate shares
            $org_company_share = $amount * 0.05; // 5% to company (admin)
            $org_aff_share = $amount * $aff_commission_percentage;  // Dynamic affiliate share
            $org_vendor_share = $amount - ($org_company_share + $org_aff_share);  // Vendor gets the rest

            // Initialize payment with Paystack
            $pay = json_decode($this->initialize_payment($formData));

            // Check if payment initialization was successful
            if ($pay && $pay->status) {
                // Save transaction data, including vendor_id
                Transaction::create([
                    'user_id' => $request->input('user_id'),
                    'email' => $request->input('email'),
                    'affiliate_id' => $affiliate_id,
                    'product_id' => $request->input('product_id'),
                    'vendor_id' => $product->user_id,  // Retrieve vendor_id from the product
                    'amount' => $amount,
                    'currency' => $request->input('currency'),
                    'status' => 'pending',
                    'org_company' => $org_company_share,
                    'org_vendor' => $org_vendor_share,
                    'org_aff' => $org_aff_share,
                    'tx_ref' => $pay->data->reference ?? null,
                    'transaction_id' => $orderId,
                ]);

                // Return the authorization URL in the JSON response
                return response()->json([
                    'success' => true,
                    'authorization_url' => $pay->data->authorization_url
                ], 200);
            } else {
                Log::error('Payment initialization failed', ['formData' => $formData, 'pay_response' => $pay]);
                return response()->json([
                    'success' => false,
                    'message' => "Something went wrong with the payment initialization."
                ], 401);
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur during the process
            Log::error('Error in make_payment method', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the payment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function payment_callback(Request $request)
{
    
    try {
        // Input validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'email' => 'required|email',
            'orderId' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for payment callback', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid input data', 'errors' => $validator->errors()], 400);
        }

        $reference = $request->input('reference');
        $email = $request->input('email');
        // $email = $request->input('email');
        $orderId = $request->input('orderId');

        // Verify payment with Paystack
        Log::info('Verifying payment with Paystack', ['reference' => $reference]);
        $response = json_decode($this->verify_payment($reference));

        if (!$response || $response->data->status !== "success") {
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'response' => $response
            ]);
            return response()->json(['message' => 'Transaction not successful', 'success' => false]);
        }

        // Check for transaction with matching email and order ID
        Log::info('Searching for transaction', [
            'email' => $email,
            'orderId' => $orderId,
            'status' => 'pending'
        ]);
        $transaction = Transaction::where('email', $email)
            ->where('transaction_id', $orderId)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$transaction) {
            Log::error('Transaction not found', [
                'email' => $email,
                'orderId' => $orderId
            ]);
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $transactionAffId = $transaction->affiliate_id;
        $transactionProductId = $transaction->product_id;
        $transactionUserId = $transaction->user_id;
        $transactionAmount = $transaction->amount;

        // Update transaction status and tx_ref
        Log::info('Updating transaction status to success', [
            'transaction_id' => $transaction->id,
            'reference' => $reference,
        ]);
        $transaction->update([
            'tx_ref' => $reference,
            'status' => 'success',
        ]);

        // Retrieve product details and vendor information
        $product = Product::find($transactionProductId);
        if (!$product) {
            Log::error('Product not found', ['product_id' => $transactionProductId]);
        }
        $product_name = $product->name ?? 'Product';
        $product_access_link = $product->access_link ?? '';

        $vendor = User::find($product->vendor_id ?? null);
        if (!$vendor) {
            Log::warning('Vendor not found for product', ['product_id' => $transactionProductId]);
        }

        // Send email to the buyer
        try {
            Log::info('Sending success email to buyer', ['email' => $email, 'product_name' => $product_name]);
            Mail::to($email)->send(new \App\Mail\PurchaseSuccessMail($product_name, $product_access_link));
        } catch (\Exception $e) {
            Log::error('Error sending email to buyer', ['email' => $email, 'error' => $e->getMessage()]);
        }

        // Notify the vendor about the sale
        if ($vendor) {
            try {
                Log::info('Sending sale notification email to vendor', [
                    'vendor_email' => $vendor->email,
                    'product_name' => $product_name,
                    'transaction_amount' => $transactionAmount
                ]);
                Mail::to($vendor->email)->send(new \App\Mail\VendorSaleNotificationMail($product_name, $transactionAmount, $email));
            } catch (\Exception $e) {
                Log::error('Error sending sale notification to vendor', ['vendor_email' => $vendor->email, 'error' => $e->getMessage()]);
            }
        }

        // Retrieve referrer information for affiliate registration link
        $refferer = User::find($transactionAffId);
        $aff_id = $refferer->aff_id ?? null;

        // If user doesn’t exist, send them a registration link
        $checkUser = User::where('email', $email)->first();
        if (!$checkUser && $aff_id) {
            try {
                Log::info('Sending registration link to new user', ['email' => $email, 'aff_id' => $aff_id]);
                Mail::to($email)->send(new \App\Mail\RegisterLink($aff_id));
            } catch (\Exception $e) {
                Log::error('Error sending registration link to user', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'transaction' => $transaction,
            'product_name' => $product_name,
            'product_access_link' => $product_access_link,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Error in payment callback', [
            'error' => $e->getMessage(),
            'reference' => $reference ?? 'N/A',
            'email' => $email ?? 'N/A',
            'orderId' => $orderId ?? 'N/A',
        ]);
        return response()->json(['success' => false, 'message' => 'An error occurred during callback processing'], 500);
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

    // public function payment_callback()
    // {
    //     $reference = request('reference');
    //     $response = json_decode($this->verify_payment($reference));
    //     $email = request('email');

    //     if ($response && $response->status == "success") {

    //         $transaction = Transaction::where('email', $email)->latest()->first();
    //         $transactionAffId = $transaction->affiliate_id;
    //         $transactionProductId = $transaction->product_id;
    //         $transactionUserId = $transaction->user_id;
    //         $transactionAmount = $transaction->amount;

    //         $refferer = User::find($transactionAffId);


    //         $aff_id = $refferer->aff_id;


    //         if ($transaction) {
    //             $transaction->update([
    //                 'tx_ref' => request('reference'),
    //                 'status' => $response->status,
    //                 'is_onboard' => 1,
    //             ]);
    //         }


    //         $product = Product::find($transactionProductId);
    //         $product_name = $product->name;
    //         $product_access_link = $product->access_link;

    //         $checkUser = User::where('email', request('email'))->first();

    //         if (!$checkUser) {
    //             Mail::to(request('email'))->send(new \App\Mail\RegisterLink($aff_id));
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'status' => $response->status,
    //             'transaction' => $transaction,
    //             'product_name' => $product_name,
    //             'product_access_link' => $product_access_link,

    //         ], 200);
    //     } else {
    //         return response()->json(['message' => 'transaction not successful', 'success' => false]);
    //     }
    // }

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
