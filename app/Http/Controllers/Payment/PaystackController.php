<?php

namespace App\Http\Controllers\Payment;

use Exception;
use App\Models\Sale;
use App\Models\User;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Enums\TransactionDescription;
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackController extends Controller
{

    // public function make_payment(Request $request)
    // {
    //     try {
    //         // Input validation
    //         $validator = Validator::make($request->all(), [
    //             'product_id' => 'required|exists:products,id',
    //             'email' => 'required|email',
    //             'aff_id' => 'required|exists:users,aff_id',

    //         ]);

    //         if ($validator->fails()) {
    //             Log::warning('Validation failed for payment callback', [
    //                 'errors' => $validator->errors()->toArray(),
    //             ]);
    //             return response()->json(['success' => false, 'message' => 'Invalid input data', 'errors' => $validator->errors()], 400);
    //         }

    //         // Check if the affiliate can sell the product
    //         if (!Helper::canSellProduct($request->input('aff_id'), $request->input('product_id'))) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Affiliate is not authorized to promote this product.'
    //             ], 403);
    //         }

    //         // Retrieve the product from the request
    //         $product = Product::find($request->input('product_id'));

    //         if (!$product) {
    //             Log::warning('Product not found', ['product_id' => $request->input('product_id')]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Product not found.'
    //             ], 404);
    //         }

    //         // Calculate the amount (Paystack expects amount in kobo or lowest currency unit)
    //         $amount = $product->price;
    //         $amountKobo = $amount * 100;
    //         $orderId = strtoupper(Str::random(5) . time());
    //         // Prepare the data for the payment
    //         $formData = [
    //             'email' => $request->input('email'),
    //             'currency' => 'NGN',
    //             'callback_url' => 'https://learnerflex.com/dashboard/product/pay' . '?p_id=' . $request->input('product_id') . '&aff_id=' . $request->input('aff_id') . '&email=' . urlencode($request->input('email')) . '&orderId=' . urlencode($orderId),
    //             'aff_id' => $request->input('aff_id'),
    //             'amount' => $amountKobo,
    //             'product_id' => $request->input('product_id'),
    //             'orderId' => $orderId,
    //         ];

    //         // Attempt to retrieve Affiliate ID, if provided
    //         $affiliate_id = null;
    //         if ($request->has('aff_id')) {
    //             $affiliate_id = User::where('aff_id', $request->input('aff_id'))->value('aff_id');
    //             if (!$affiliate_id) {
    //                 Log::info('Affiliate not found', ['aff_id' => $request->input('aff_id')]);
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Affiliate not found.'
    //                 ], 404);
    //             }
    //         }

    //         // Fetch the product's affiliate commission percentage
    //         $aff_commission_percentage = $product->commission ? $product->commission / 100 : 0;

    //         $org_company_share = $amountKobo * 0.05; // 5% of the amount
    //         $org_aff_share = $amountKobo * $aff_commission_percentage; // Affiliate share in kobo
    //         $org_vendor_share = $amountKobo - ($org_company_share + $org_aff_share); // Vendor share in kobo


    //         // Initialize payment with Paystack
    //         $pay = json_decode($this->initialize_payment($formData));

    //         // Check if payment initialization was successful
    //         if ($pay && $pay->status) {
    //             // Save transaction data, including vendor_id
    //             $tr = Transaction::create([
    //                 'user_id' => $request->input('user_id'),
    //                 'email' => $request->input('email'),
    //                 'affiliate_id' => $affiliate_id,
    //                 'product_id' => $request->input('product_id'),
    //                 'vendor_id' => $product->user_id,
    //                 'amount' => $amountKobo,
    //                 'currency' => 'NGN',
    //                 'status' => 'pending',
    //                 'org_company' => $org_company_share,
    //                 'org_vendor' => $org_vendor_share,
    //                 'org_aff' => $org_aff_share,
    //                 'tx_ref' => $pay->data->reference ?? null,
    //                 'description' => TransactionDescription::PRODUCT_SALE->value,
    //                 'transaction_id' => $orderId,
    //             ]);
    //             // Return the authorization URL in the JSON response
    //             return response()->json([
    //                 'success' => true,
    //                 'authorization_url' => $pay->data->authorization_url
    //             ], 200);
    //         } else {
    //             Log::error('Payment initialization failed', ['formData' => $formData, 'pay_response' => $pay]);
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Something went wrong with the payment initialization."
    //             ], 500);
    //         }
    //     } catch (\Exception $e) {
    //         // Log any exceptions that occur during the process
    //         Log::error('Error in make_payment method', [
    //             'error' => $e->getMessage(),
    //             'request_data' => $request->all()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while processing the payment.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function make_payment(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'email' => 'required|email',
            'aff_id' => 'required|exists:users,aff_id',

        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for payment callback', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid input data', 'errors' => $validator->errors()], 400);
        }

        // Check if the affiliate can sell the product
        if (!Helper::canSellProduct($request->input('aff_id'), $request->input('product_id'))) {
            return response()->json([
                'success' => false,
                'message' => 'Affiliate is not authorized to promote this product.'
            ], 403);
        }

        // Retrieve the product from the request
        $product = Product::find($request->input('product_id'));

        if (!$product) {
            Log::warning('Product not found', ['product_id' => $request->input('product_id')]);
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        try {
            // Calculate the amount (Paystack expects amount in kobo or lowest currency unit)
            $amount = $product->price;
            $amountKobo = $amount * 100;
            $orderId = strtoupper(Str::random(5) . time());
            // Prepare the data for the payment
            $formData = [
                'email' => $request->input('email'),
                'currency' => 'NGN',
                'callback_url' => 'https://learnerflex.com/dashboard/product/pay' . '?p_id=' . $request->input('product_id') . '&aff_id=' . $request->input('aff_id') . '&email=' . urlencode($request->input('email')) . '&orderId=' . urlencode($orderId),
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

            $org_company_share = $amountKobo * 0.05; // 5% of the amount
            $org_aff_share = $amountKobo * $aff_commission_percentage; // Affiliate share in kobo
            $org_vendor_share = $amountKobo - ($org_company_share + $org_aff_share); // Vendor share in kobo


            try {
                // Initialize payment with Paystack
                $pay = Paystack::getAuthorizationUrl($formData);
                // Save transaction data, including vendor_id
                Transaction::create([
                    'user_id' => $request->input('user_id'),
                    'email' => $request->input('email'),
                    'affiliate_id' => $affiliate_id,
                    'product_id' => $request->input('product_id'),
                    'vendor_id' => $product->user_id,
                    'amount' => $amountKobo,
                    'currency' => 'NGN',
                    'status' => 'pending',
                    'org_company' => $org_company_share,
                    'org_vendor' => $org_vendor_share,
                    'org_aff' => $org_aff_share,
                    'tx_ref' => null,
                    'description' => TransactionDescription::PRODUCT_SALE->value,
                    'transaction_id' => $orderId,
                ]);
                // Return the authorization URL in the JSON response
                return response()->json([
                    'success' => true,
                    'authorization_url' => $pay->url
                ], 200);
            } catch (\Exception $e) {
                \Log::error(['Payment Initialization failed: ' . $e->getMessage(), 'formData' => $formData,]);
                // Handle exception
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong with the payment initialization.',
                ], 500);
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
                'aff_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for payment callback', [
                    'errors' => $validator->errors()->toArray(),
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid input data', 'errors' => $validator->errors()], 400);
            }

            $reference = $request->input('reference');
            $email = $request->input('email');
            $orderId = $request->input('orderId');

            // Verify payment with Paystack
            Log::info('Verifying payment with Paystack', ['reference' => $reference]);
            $response = json_decode($this->verify_payment($reference));

            if (!$response || !isset($response->data) || $response->data->status !== "success") {
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

            // Update transaction status and tx_ref
            Log::info('Updating transaction status to success', [
                'transaction_id' => $transaction->id,
                'reference' => $reference,
            ]);
            $transaction->update([
                'tx_ref' => $reference,
                'status' => 'success',
                'description' => 'product_sale'
            ]);

            // Retrieve product and vendor information
            // $product = Product::find($transaction->product_id);\
            $product = Product::find($transaction->product_id);
            $vendor =  User::find($product->user_id);

            // Insert data into the sales table using transaction data
            $sale = Sale::create([
                'transaction_id' => $transaction->id,
                'product_id' => $transaction->product_id,
                'vendor_id' => $transaction->vendor_id,
                'affiliate_id' => $transaction->affiliate_id,
                'amount' => $transaction->amount,
                'status' => 'success',
                'commission' => $product->commission ?? 0,
                'currency' => $transaction->currency,
                'email' => $transaction->email,
                'org_vendor' => $transaction->org_vendor,
                'org_aff' => $transaction->org_aff,
                'org_company' => $transaction->org_company,
            ]);

            if (!$sale) {
                Log::error('Sale not saved', ['transaction_id' => $transaction->id]);
            }

            // Prepare email data
            $product_name = $product->name ?? 'Product';
            $product_type = $product->type ?? '';
            $user_name = $transaction->email; // Assuming user's email is used as their name for simplicity
            $email = $transaction->email;
            $refferer = User::where('aff_id', $transaction->affiliate_id)->first();
            $product_access_link = $product->access_link ?? '#';
            $mentor_name = $vendor->name ?? 'Mentor';
            $product_image = $product->image;

            // Add the try-catch block for sending emails based on product type
            try {
                // If user doesn't exist, send registration link

                $aff_id = $refferer->aff_id ?? null;

                // For eBooks
                if ($product_type === 'ebook') {
                    $download_link = Helper::generateDownloadLink($transaction->product_id);
                    Mail::to($email)->send(new \App\Mail\EbookPurchaseSuccessMail($user_name, $product_name, $product_access_link, $download_link, $mentor_name, $aff_id));
                }
                // For Digital Products
                elseif ($product_type === 'digital') {
                    Mail::to($email)->send(new \App\Mail\DigitalProductPurchaseSuccessMail($user_name, $product_name, $email, $product_access_link, $aff_id));
                }
                // For Mentorship
                elseif ($product_type === 'mentorship') {
                    // Assuming the mentor's name is the vendor's name
                    Mail::to($email)->send(new \App\Mail\MentorshipPurchaseSuccessMail($user_name, $mentor_name, $product_access_link, $aff_id, $product_name));
                }
            } catch (\Exception $e) {
                Log::error('Error sending email after payment', ['error' => $e->getMessage()]);
            }

            // Notify the vendor about the sale
            if ($vendor) {

                try {
                    $affiliate_name = $refferer->name ?? null;
                    $vendor_name =  $vendor->name;
                    Mail::to($vendor->email)->send(new \App\Mail\VendorSaleNotificationMail($product_name, $transaction->org_vendor, $email, $reference, $vendor_name, $affiliate_name));
                } catch (\Exception $e) {
                    Log::error('Error sending sale notification to vendor', ['vendor_email' => $vendor->email, 'error' => $e->getMessage()]);
                }
            }

            if ($refferer) {
                // Prepare data for the affiliate email
                $affiliate_name = $refferer->name;
                $product_name = $product->name ?? 'Product';
                $commission = $transaction->org_aff ?? 0;
                $customer_name = $transaction->email; // Assuming the customer name is stored in the email
                $customer_email = $transaction->email;
                $reference_id = $transaction->tx_ref;

                // Send the affiliate notification email
                try {
                    Mail::to($refferer->email)->send(new \App\Mail\AffiliateSaleNotificationMail(
                        $affiliate_name,
                        $product_name,
                        $commission,
                        $customer_name,
                        $customer_email,
                        $reference_id
                    ));
                } catch (\Exception $e) {
                    Log::error('Error sending affiliate sale notification email', [
                        'affiliate_email' => $refferer->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }


            return response()->json([
                'success' => true,
                'status' => 'success',
                'transaction' => $transaction,
                'product_name' => $product_name,
                'product_access_link' => $product_access_link,
                'product_image' => $product_image,
                'vendor_id' => $product->vendor_id,
                'vendor_user_id' => $product->user_id,
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

    private function verify_payment($reference)
    {
        $secret_key = env('PAYSTACK_SECRET_KEY'); // Replace with your actual secret key
        $url = "https://api.paystack.co/transaction/verify/{$reference}";

        $headers = [
            'Authorization: Bearer ' . $secret_key,
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

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

    //     if (curl_errno($ch)) {
    //         Log::error('Curl error: ' . curl_error($ch));
    //         return false;
    //     }


    //     curl_close($ch);


    //     return $result;
    // }
}
