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
use App\Mail\RegisterSuccess;
use App\Models\TemporaryUsers;
use PhpParser\Node\Stmt\TryCatch;
use App\Events\PaymentReceiptSent;
use Illuminate\Support\Facades\DB;
use App\Mail\MarketplaceUnlockMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Enums\TransactionDescription;
use App\Mail\EbookPurchaseSuccessMail;
use App\Mail\IssueProcessingTransaction;
use App\Mail\VendorSaleNotificationMail;
use Illuminate\Support\Facades\Validator;
use App\Mail\AffiliateSaleNotificationMail;
use App\Mail\MentorshipPurchaseSuccessMail;
use App\Mail\UserPaymentFailedNotification;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Mail\DigitalProductPurchaseSuccessMail;

class PaystackController extends Controller
{

    public function handleWebhook(Request $request)
    {
        // Verify that the request came from Paystack
        if (!$this->verifyWebhookSignature($request)) {
            $payload = $request->all();
            $email = $payload['data']['customer']['email'] ?? 'Unknown';
            $orderId = $payload['data']['reference'] ?? 'Unknown';
            $customMessage = 'Paystack webhook signature verification failed. Suspected hacking attempt.';

            Log::warning('Invalid Paystack webhook signature', [
                'email' => $email,
                'orderId' => $orderId
            ]);
            $this->sendAdminTransactionError($email, $orderId, $customMessage);

            return http_response_code(200);
        }

        $payload = $request->all();
        Log::info('Handling webhook', [
            'Payload' => $payload,
        ]);

        // Handle the event
        switch ($payload['event']) {
            case 'charge.success':
                return $this->handleSuccessfulCharge($payload['data']);
            case 'charge.failed':
                return $this->handleFailedCharge($payload['data']);
                // Add more cases for other event types if needed
            default:
                return http_response_code(200);
        }
    }

    private function verifyWebhookSignature(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $secret = config('services.paystack.secret_key');

        return $signature === hash_hmac('sha512', $payload, $secret);
    }

    private function handleSuccessfulCharge($data)
    {
        $reference = $data['reference'];
        $email = $data['customer']['email'];
        $orderId = $data['metadata']['transaction_id'];

        Log::info('Handling successful charge', [
            'reference' => $reference,
            'email' => $email,
            'orderId' => $orderId,
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
            $customMessage = "This transaction could not be found in the database but the person was charged. Please check the logs and verify the payment." . print_r($data, true);

            // Send email using the custom mailer
            $this->sendAdminTransactionError($email, $orderId, $customMessage);
            return http_response_code(200);
        }

        // Update transaction status and tx_ref
        $transaction->update([
            'tx_ref' => $reference,
            'status' => $data['status'],
        ]);

        // Process the successful payment based on the transaction type
        switch ($transaction->description) {
            case TransactionDescription::PRODUCT_SALE->value:
                $this->processProductSale($transaction);
                break;
            case TransactionDescription::SIGNUP_FEE->value:
                $this->processSignupFee($transaction);
                break;
            case TransactionDescription::MARKETPLACE_UNLOCK->value:
                $this->processMarketAccess($transaction);
                break;
            default:
                Log::error('Unknown transaction type', ['transaction_id' => $transaction->id]);
                return http_response_code(200);
        }

        // return response()->json(['message' => 'Webhook processed successfully'], 200);
        return http_response_code(200);
    }

    public function processProductSale(Transaction $transaction)
    {
        DB::beginTransaction();

        try {
            // Retrieve product and vendor information
            $product = Product::findOrFail($transaction->product_id);
            $vendor = User::findOrFail($product->user_id);

            // Create sale record
            $sale = $this->createSaleRecord($transaction, $product);

            // Process emails
            $this->processEmails($transaction, $product, $vendor);

            DB::commit();

            // Log successful processing
            Log::info('Product sale processed successfully', [
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'vendor_id' => $vendor->id
            ]);

            return http_response_code(200);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error processing product sale', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);
            $customMessage = "Error processing product sale ";
            $this->sendAdminTransactionError($transaction->email, $transaction->transaction_id, $customMessage);

            $transaction->update([
                'response_data' => 'Error processing product sale',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createSaleRecord(Transaction $transaction, Product $product)
    {
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
            throw new Exception('Failed to create sale record');
        }

        return $sale;
    }

    private function processEmails(Transaction $transaction, Product $product, User $vendor)
    {
        $referrer = User::where('aff_id', $transaction->affiliate_id)->first();

        $this->sendCustomerEmail($transaction, $product, $vendor, $referrer);
        $this->sendVendorEmail($transaction, $product, $vendor, $referrer);
        $this->sendAffiliateEmail($transaction, $product, $referrer);
    }

    private function sendCustomerEmail(Transaction $transaction, Product $product, User $vendor, ?User $referrer)
    {
        $user_name = $transaction->email;
        $product_name = $product->name ?? 'Product';
        $product_type = $product->type ?? '';
        $product_access_link = $product->access_link ?? '#';
        $mentor_name = $vendor->name ?? 'Mentor';
        $aff_id = $referrer->aff_id ?? null;

        try {
            switch ($product_type) {
                case 'ebook':
                    $download_link = Helper::generateDownloadLink($transaction->product_id);
                    Mail::to($transaction->email)->send(new EbookPurchaseSuccessMail($user_name, $product_name, $product_access_link, $download_link, $mentor_name, $aff_id));
                    break;
                case 'digital':
                    Mail::to($transaction->email)->send(new DigitalProductPurchaseSuccessMail($user_name, $product_name, $transaction->email, $product_access_link, $aff_id));
                    break;
                case 'mentorship':
                    Mail::to($transaction->email)->send(new MentorshipPurchaseSuccessMail($user_name, $mentor_name, $product_access_link, $aff_id, $product_name));
                    break;
                default:
                    Log::warning('Unknown product type for email', ['product_type' => $product_type, 'transaction_id' => $transaction->id]);
            }
        } catch (Exception $e) {
            Log::error('Error sending customer email', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);
            // We don't throw here to allow the process to continue
        }
    }

    private function sendVendorEmail(Transaction $transaction, Product $product, User $vendor, ?User $referrer)
    {
        try {
            $affiliate_name = $referrer->name ?? null;
            Mail::to($vendor->email)->send(new VendorSaleNotificationMail(
                $product->name ?? 'Product',
                $transaction->org_vendor,
                $transaction->email,
                $transaction->tx_ref,
                $vendor->name,
                $affiliate_name
            ));
        } catch (Exception $e) {
            Log::error('Error sending vendor email', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'vendor_id' => $vendor->id
            ]);
            // We don't throw here to allow the process to continue
        }
    }

    private function sendAffiliateEmail(Transaction $transaction, Product $product, ?User $referrer)
    {
        if ($referrer) {
            try {
                Mail::to($referrer->email)->send(new AffiliateSaleNotificationMail(
                    $referrer->name,
                    $product->name ?? 'Product',
                    $transaction->org_aff ?? 0,
                    $transaction->email,
                    $transaction->email,
                    $transaction->tx_ref
                ));
            } catch (Exception $e) {
                Log::error('Error sending affiliate email', [
                    'error' => $e->getMessage(),
                    'transaction_id' => $transaction->id,
                    'affiliate_id' => $referrer->id
                ]);
                // We don't throw here to allow the process to continue
            }
        }
    }


    private function handleFailedCharge($data)
    {

        $transaction = Transaction::where('email', $data['customer']['email'])
            ->where('transaction_id', $data['metadata']['transaction_id'])
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            $email = $data['customer']['email'];
            $orderId = $data['metadata']['transaction_id'];
            Log::error('Transaction not found', [
                'email' => $data['customer']['email'],
                'orderId' => $orderId
            ]);
            $customMessage = "This is a failed transaction on paystack.com and this transaction could not be found in the database. Please check the logs and verify the payment. ";

            // Send email using the custom mailer
            try {
                Mail::mailer('admin_mailer')->to('learnerflexltd@gmail.com')->send(new IssueProcessingTransaction($email, $orderId, $customMessage));
            } catch (\Throwable $th) {
                Log::error('Transaction not found', [
                    'error' => $th->getMessage(),
                    'email' => $email,
                    'orderId' => $orderId
                ]);
            }
            return http_response_code(200);
        }

        DB::transaction(function () use ($transaction, $data) {
            $transaction->update([
                'status' => $data['status'],
                'tx_ref' => $data['reference'],
                'response_data' => 'failed charge by paystack',
                'error' => 'failed charge by paystack',
            ]);

            // Log the failed charge
            Log::info('Charge failed', [
                'transaction_id' => $transaction->id,
                'email' => $transaction->email,
                'amount' => $data['amount'] / 100, // Convert from kobo to Naira
                'message' => $data['gateway_response'] ?? 'Unknown error'
            ]);

            // Send failure notification email to the customer
            $amount = $data['amount'] / 100; // Convert from kobo to Naira
            $message = $data['gateway_response'] ?? 'Unknown error';

            Mail::to($transaction->email)->send(new UserPaymentFailedNotification($transaction, $amount, $message));
        });

        return http_response_code(200);
    }

    private function sendAdminTransactionError($email, $orderId, $customMessage)
    {
        $defaultMessage = "This is a failed transaction on paystack.com or this transaction could not be found in the database. Please check the logs and verify the payment.";
        $message = $customMessage ?? $defaultMessage;

        Log::error('Transaction Error', [
            'email' => $email,
            'orderId' => $orderId,
            'message' => $message
        ]);

        try {
            Mail::mailer('admin_mailer')
                ->to('learnerflexltd@gmail.com')
                ->send(new IssueProcessingTransaction($email, $orderId, $message));
        } catch (\Throwable $th) {
            Log::error('Failed to send error notification email', [
                'error' => $th->getMessage(),
                'email' => $email,
                'orderId' => $orderId
            ]);
        }
    }

    private function processSignupFee($transaction)
    {
        try {
            // Retrieve temporary user data from the database
            $temporaryUser  = TemporaryUsers::where('email', $transaction->email)->where('order_id', $transaction->transaction_id)->first();

            if (!$temporaryUser) {
                // Log the error
                Log::error('Temporary user data not found for signup fee payment', [
                    'transaction_id' => $transaction->transaction_id,
                    'email' => $transaction->email,
                ]);
                $customMessage = "Please register the user with email, {$$transaction->email}, with order id {$transaction->transaction_id}. Their data was not found after payment, register them manually on the platform";

                $this->sendAdminTransactionError($transaction->email, $transaction->transaction_id, $customMessage);
                $transaction->update([
                    'response_data' => "Could not find user with email {$transaction->email}",
                    'error' => 'User not found',
                ]);
                return http_response_code(200);
            }

            // Generate a unique aff_id for the new user
            do {
                $aff_id = Str::random(8);
                $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
            } while ($exists);

            // Create the new user
            $user = User::create([
                'aff_id' => $aff_id,
                'name' => $temporaryUser->name,
                'email' => $temporaryUser->email,
                'phone' => $temporaryUser->phone,
                'password' => $temporaryUser->password, // Already hashed
                'country' => null,
                'refferal_id' => null,
                'image' => null,
                'role' => 'affiliate',
                'market_access' => true,
            ]);

            // Delete the temporary user data
            $temporaryUser->delete();

            // Generate token and send confirmation email
            $token = $user->createToken('YourAppName')->plainTextToken;
            $name = $temporaryUser->name;
            Mail::to($transaction->email)->send(new \App\Mail\RegisterSuccess($name));

            // Log the successful registration
            Log::info('Signup fee payment processed successfully', [
                'transaction_id' => $transaction->transaction_id,
                'email' => $transaction->email,
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error processing signup fee payment', [
                'transaction_id' => $transaction->transaction_id,
                'email' => $transaction->email,
                'error' => $e->getMessage(),
            ]);
            $customMessage = "Error processing signup fee payment" . $e->getMessage();
            $this->sendAdminTransactionError($transaction->email, $transaction->transaction_id, $customMessage);
            $transaction->update([
                'response_data' => 'Error processing signup fee payment',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processMarketAccess($transaction)
    {
        // Locate the user using the email from the transaction
        $user = User::where('email', $transaction->email)->first();

        if (!$user) {
            Log::error('User  not found for market access', [
                'email' => $transaction->email,
                'transaction_id' => $transaction->transaction_id,
            ]);
            $customMessage = "Decide what to do, the user with this email: {$transaction->email}, transaction_id: {$transaction->transaction_id}. what not found after payment was successful, pls check with paystack as well";
            $this->sendAdminTransactionError($transaction->email, $transaction->transaction_id, $customMessage);
            $transaction->update([
                'response_data' => 'Could not found the user, please try again or report the issue',
                'error' => 'User not found',
            ]);
            return http_response_code(200);
        }

        // Update the user's market access and clear referral ID
        $user->update([
            'market_access' => true,
        ]);

        // Send the market access email
        $name = $user->name ?? 'Valued User'; // Fallback to a default name if not available
        try {
            Mail::to($transaction->email)->send(new \App\Mail\MarketplaceUnlockMail($name));
        } catch (\Exception $e) {
            Log::error('Error sending market access email:', ['error' => $e->getMessage()]);
        }

        Log::info('Market access unlocked successfully', [
            'transaction_id' => $transaction->transaction_id,
            'email' => $transaction->email,
        ]);
    }
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
            $orderId = 'TXN' . strtoupper(Str::random(5) . time());
            // Prepare the data for the payment
            $formData = [
                'email' => $request->input('email'),
                'currency' => 'NGN',
                'amount' => $amountKobo,
                'metadata' => [
                    'transaction_id' => $orderId, // Add this line
                    'product_id' => $request->input('product_id'),
                    'aff_id' => $request->input('aff_id'),
                    'description' => TransactionDescription::PRODUCT_SALE->value,
                ],
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
                    'authorization_url' => $pay->url,
                    'transaction_id' => $orderId,
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

    // public function payment_callback(Request $request)
    // {
    //     try {
    //         // Input validation
    //         $validator = Validator::make($request->all(), [
    //             'reference' => 'required|string',
    //             'email' => 'required|email',
    //             'orderId' => 'required|string',
    //             'aff_id' => 'required|string',
    //         ]);

    //         if ($validator->fails()) {
    //             Log::warning('Validation failed for payment callback', [
    //                 'errors' => $validator->errors()->toArray(),
    //             ]);
    //             return response()->json(['success' => false, 'message' => 'Invalid input data', 'errors' => $validator->errors()], 400);
    //         }

    //         $reference = $request->input('reference');
    //         $email = $request->input('email');
    //         $orderId = $request->input('orderId');

    //         // Verify payment with Paystack
    //         Log::info('Verifying payment with Paystack', ['reference' => $reference]);
    //         $response = json_decode($this->verify_payment($reference));

    //         if (!$response || !isset($response->data) || $response->data->status !== "success") {
    //             Log::error('Payment verification failed', [
    //                 'reference' => $reference,
    //                 'response' => $response
    //             ]);
    //             return response()->json(['message' => 'Transaction not successful', 'success' => false]);
    //         }

    //         // Check for transaction with matching email and order ID
    //         Log::info('Searching for transaction', [
    //             'email' => $email,
    //             'orderId' => $orderId,
    //             'status' => 'pending'
    //         ]);
    //         $transaction = Transaction::where('email', $email)
    //             ->where('transaction_id', $orderId)
    //             ->where('status', 'pending')
    //             ->latest()
    //             ->first();

    //         if (!$transaction) {
    //             Log::error('Transaction not found', [
    //                 'email' => $email,
    //                 'orderId' => $orderId
    //             ]);
    //             return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
    //         }

    //         // Update transaction status and tx_ref
    //         Log::info('Updating transaction status to success', [
    //             'transaction_id' => $transaction->id,
    //             'reference' => $reference,
    //         ]);
    //         $transaction->update([
    //             'tx_ref' => $reference,
    //             'status' => 'success',
    //             'description' => 'product_sale'
    //         ]);

    //         // Retrieve product and vendor information
    //         // $product = Product::find($transaction->product_id);\
    //         $product = Product::find($transaction->product_id);
    //         $vendor =  User::find($product->user_id);

    //         // Insert data into the sales table using transaction data
    //         $sale = Sale::create([
    //             'transaction_id' => $transaction->id,
    //             'product_id' => $transaction->product_id,
    //             'vendor_id' => $transaction->vendor_id,
    //             'affiliate_id' => $transaction->affiliate_id,
    //             'amount' => $transaction->amount,
    //             'status' => 'success',
    //             'commission' => $product->commission ?? 0,
    //             'currency' => $transaction->currency,
    //             'email' => $transaction->email,
    //             'org_vendor' => $transaction->org_vendor,
    //             'org_aff' => $transaction->org_aff,
    //             'org_company' => $transaction->org_company,
    //         ]);

    //         if (!$sale) {
    //             Log::error('Sale not saved', ['transaction_id' => $transaction->id]);
    //         }

    //         // Prepare email data
    //         $product_name = $product->name ?? 'Product';
    //         $product_type = $product->type ?? '';
    //         $user_name = $transaction->email; // Assuming user's email is used as their name for simplicity
    //         $email = $transaction->email;
    //         $refferer = User::where('aff_id', $transaction->affiliate_id)->first();
    //         $product_access_link = $product->access_link ?? '#';
    //         $mentor_name = $vendor->name ?? 'Mentor';
    //         $product_image = $product->image;

    //         // Add the try-catch block for sending emails based on product type
    //         try {
    //             // If user doesn't exist, send registration link

    //             $aff_id = $refferer->aff_id ?? null;

    //             // For eBooks
    //             if ($product_type === 'ebook') {
    //                 $download_link = Helper::generateDownloadLink($transaction->product_id);
    //                 Mail::to($email)->send(new \App\Mail\EbookPurchaseSuccessMail($user_name, $product_name, $product_access_link, $download_link, $mentor_name, $aff_id));
    //             }
    //             // For Digital Products
    //             elseif ($product_type === 'digital') {
    //                 Mail::to($email)->send(new \App\Mail\DigitalProductPurchaseSuccessMail($user_name, $product_name, $email, $product_access_link, $aff_id));
    //             }
    //             // For Mentorship
    //             elseif ($product_type === 'mentorship') {
    //                 // Assuming the mentor's name is the vendor's name
    //                 Mail::to($email)->send(new \App\Mail\MentorshipPurchaseSuccessMail($user_name, $mentor_name, $product_access_link, $aff_id, $product_name));
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error sending email after payment', ['error' => $e->getMessage()]);
    //         }

    //         // Notify the vendor about the sale
    //         if ($vendor) {

    //             try {
    //                 $affiliate_name = $refferer->name ?? null;
    //                 $vendor_name =  $vendor->name;
    //                 Mail::to($vendor->email)->send(new \App\Mail\VendorSaleNotificationMail($product_name, $transaction->org_vendor, $email, $reference, $vendor_name, $affiliate_name));
    //             } catch (\Exception $e) {
    //                 Log::error('Error sending sale notification to vendor', ['vendor_email' => $vendor->email, 'error' => $e->getMessage()]);
    //             }
    //         }

    //         if ($refferer) {
    //             // Prepare data for the affiliate email
    //             $affiliate_name = $refferer->name;
    //             $product_name = $product->name ?? 'Product';
    //             $commission = $transaction->org_aff ?? 0;
    //             $customer_name = $transaction->email; // Assuming the customer name is stored in the email
    //             $customer_email = $transaction->email;
    //             $reference_id = $transaction->tx_ref;

    //             // Send the affiliate notification email
    //             try {
    //                 Mail::to($refferer->email)->send(new \App\Mail\AffiliateSaleNotificationMail(
    //                     $affiliate_name,
    //                     $product_name,
    //                     $commission,
    //                     $customer_name,
    //                     $customer_email,
    //                     $reference_id
    //                 ));
    //             } catch (\Exception $e) {
    //                 Log::error('Error sending affiliate sale notification email', [
    //                     'affiliate_email' => $refferer->email,
    //                     'error' => $e->getMessage(),
    //                 ]);
    //             }
    //         }


    //         return response()->json([
    //             'success' => true,
    //             'status' => 'success',
    //             'transaction' => $transaction,
    //             'product_name' => $product_name,
    //             'product_access_link' => $product_access_link,
    //             'product_image' => $product_image,
    //             'vendor_id' => $product->vendor_id,
    //             'vendor_user_id' => $product->user_id,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error in payment callback', [
    //             'error' => $e->getMessage(),
    //             'reference' => $reference ?? 'N/A',
    //             'email' => $email ?? 'N/A',
    //             'orderId' => $orderId ?? 'N/A',
    //         ]);
    //         return response()->json(['success' => false, 'message' => 'An error occurred during callback processing'], 500);
    //     }
    // }

    // private function processProductSale($transaction)
    // {
    //     try {
    //         // Retrieve product and vendor information
    //         $product = Product::find($transaction->product_id);
    //         $vendor = User::find($product->vendor_id); // Use vendor_id instead of user_id

    //         // Insert data into the sales table using transaction data
    //         $sale = Sale::create([
    //             'transaction_id' => $transaction->id,
    //             'product_id' => $transaction->product_id,
    //             'vendor_id' => $transaction->vendor_id,
    //             'affiliate_id' => $transaction->affiliate_id,
    //             'amount' => $transaction->amount,
    //             'status' => 'success',
    //             'commission' => $product->commission ?? 0,
    //             'currency' => $transaction->currency,
    //             'email' => $transaction->email,
    //             'org_vendor' => $transaction->org_vendor,
    //             'org_aff' => $transaction->org_aff,
    //             'org_company' => $transaction->org_company,
    //         ]);

    //         if (!$sale) {
    //             Log::error('Sale not saved', ['transaction_id' => $transaction->id]);
    //             return; // Exit if sale could not be recorded
    //         }

    //         // Prepare email data
    //         $product_name = $product->name ?? 'Product';
    //         $product_type = $product->type ?? '';
    //         $user_name = $transaction->email; // Assuming user's email is used as their name for simplicity
    //         $email = $transaction->email;
    //         $refferer = User::where('aff_id', $transaction->affiliate_id)->first();
    //         $product_access_link = $product->access_link ?? '#';
    //         $mentor_name = $vendor->name ?? 'Mentor';
    //         $product_image = $product->image;

    //         // Send purchase confirmation email based on product type
    //         $this->sendPurchaseConfirmationEmail($product_type, $user_name, $email, $product_name, $product_access_link, $mentor_name, $refferer);

    //         // Notify the vendor about the sale
    //         if ($vendor) {
    //             $this->notifyVendor($vendor, $product_name, $transaction, $refferer);
    //         }

    //         // Notify the affiliate if applicable
    //         if ($refferer) {
    //             $this->notifyAffiliate($refferer, $product_name, $transaction);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Error processing product sale', [
    //             'transaction_id' => $transaction->id,
    //             'error' => $e->getMessage(),
    //         ]);
    //     }
    // }

    // private function sendPurchaseConfirmationEmail($product_type, $user_name, $email, $product_name, $product_access_link, $mentor_name, $refferer)
    // {
    //     try {
    //         $aff_id = $refferer->aff_id ?? null;

    //         // For eBooks
    //         if ($product_type === 'ebook') {
    //             $download_link = Helper::generateDownloadLink($product_name);
    //             Mail::to($email)->send(new \App\Mail\EbookPurchaseSuccessMail($user_name, $product_name, $product_access_link, $download_link, $mentor_name, $aff_id));
    //         }
    //         // For Digital Products
    //         elseif ($product_type === 'digital') {
    //             Mail::to($email)->send(new \App\Mail\DigitalProductPurchaseSuccessMail($user_name, $product_name, $email, $product_access_link, $aff_id));
    //         }
    //         // For Mentorship
    //         elseif ($product_type === 'mentorship') {
    //             Mail::to($email)->send(new \App\Mail\MentorshipPurchaseSuccessMail($user_name, $mentor_name, $product_access_link, $aff_id, $product_name));
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Error sending email after payment', ['error' => $e->getMessage()]);
    //     }
    // }

    // private function notifyVendor($vendor, $product_name, $transaction, $refferer)
    // {
    //     try {
    //         $affiliate_name = $refferer->name ?? null;
    //         $vendor_name = $vendor->name;
    //         Mail::to($vendor->email)->send(new \App\Mail\VendorSaleNotificationMail($product_name, $transaction->org_vendor, $transaction->email, $transaction->tx_ref, $vendor_name, $affiliate_name));
    //     } catch (\Exception $e) {
    //         Log::error('Error sending sale notification to vendor', ['vendor_email' => $vendor->email, 'error' => $e->getMessage()]);
    //     }
    // }

    // private function notifyAffiliate($refferer, $product_name, $transaction)
    // {
    //     try {
    //         $affiliate_name = $refferer->name;
    //         Mail::to($refferer->email)->send(new \App\Mail\AffiliateSaleNotificationMail($product_name, $transaction->org_aff, $transaction->email, $transaction->tx_ref, $affiliate_name));
    //     } catch (\Exception $e) {
    //         Log::error('Error sending sale notification to affiliate', ['affiliate_email' => $refferer->email, 'error' => $e->getMessage()]);
    //     }
    // }

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
}
