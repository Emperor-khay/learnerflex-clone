<?php

namespace App\Http\Controllers\Auths;

use Log;
use App\Models\User;
use App\Rules\ReCaptchaV3;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Service\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\TemporaryUsers;
use App\Enums\VendorStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Enums\TransactionDescription;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Notifications\Notification;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Notifications\RegistrationIssueNotification;


class RegisterController extends Controller
{

    public function initiateRegistration(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users', // Changed to `phone`
            'password' => 'required|string|confirmed|min:4',
            'aff_id' => 'nullable|string|max:30', // aff_id is optional
            'g-recaptcha-response' => ['required', new ReCaptchaV3('submitContact')]
        ]);


        // If aff_id is present, register the user directly
        if ($request->filled('aff_id')) {
            return $this->storeUser($validatedData, $request->aff_id);
        } else {
            // Hash password before storing in session (for security)
            $hashedPassword = Hash::make($validatedData['password']);

            // Proceed to initiate payment
            try {
                // Generate a unique order ID
                $orderID = strtoupper(Str::random(5) . time());

                // Prepare the payment data
                $formData = [
                    'email' => $validatedData['email'],  // Use validated email
                    'amount' => 5100 * 100, // Amount in kobo (NGN)
                    'currency' => 'NGN',
                    'callback_url' => route("auth.payment.callback") . '?email=' . urlencode($request->email) . '&orderId=' . urlencode($orderID), // Corrected query string
                    'orderID' => $orderID,
                ];

                // Initialize payment with Paystack using Unicodeveloper package
                $paymentData = Paystack::getAuthorizationUrl($formData);

                // Store the user data in the temporary users table
                TemporaryUsers::updateOrCreate(
                    ['email' => $validatedData['email']], // Search criteria
                    [
                        'name' => $validatedData['name'],
                        'phone' => $validatedData['phone'],
                        'password' => $hashedPassword, // Store hashed password
                        'aff_id' => null,
                        'order_id' => $orderID,
                    ]
                );

                // Store transaction details in the database
                Transaction::create([
                    'user_id' => 0, // User ID not yet available
                    'email' =>  $validatedData['email'],
                    'affiliate_id' => 0,
                    'product_id' => 0,
                    'amount' => $formData['amount'],
                    'currency' => $formData['currency'],
                    'status' => 'pending',
                    'org_company' => 0,
                    'org_vendor' => 0,
                    'org_aff' => 0,
                    'market_access' => true,
                    'description' => TransactionDescription::SIGNUP_FEE->value,
                    'tx_ref' => null,
                    'transaction_id' => $orderID, // Save the dynamic order ID
                ]);

                // Return the authorization URL in the response
                return response()->json([
                    'success' => true,
                    'authorization_url' => $paymentData, // Correct response for the payment URL
                ], 200);
            } catch (\Exception $e) {
                \Log::error('Payment Initialization Error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }


    public function storeUser($validatedData, $referral)
    {
        $refferal_check = User::where('aff_id', $referral)->first();
        if (!$refferal_check) {
            return response()->json([
                'success' => false,
                'message' => 'Affiliate id does not exist.',
            ]);
        }

        // Check for valid transactions
        $validTransaction = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->where('transactions.email', $validatedData['email'])
            ->where('transactions.affiliate_id', $referral)
            ->where('transactions.status', 'success')
            ->whereNotNull('transactions.product_id')
            ->where('products.is_affiliated', true) // Check if the product is affiliated
            ->exists();

        if (!$validTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'No valid transaction found to allow registration. Only affiliated products are eligible.',
            ]);
        }
        // Hash the password
        $hashedPassword = Hash::make($validatedData['password']);

        $aff_id = null;
        // Generate a unique aff_id for the new user
        do {
            $aff_id = Str::random(8);
            $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
        } while ($exists);
        // Create the new user
        User::create([
            'aff_id' => $aff_id,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => $hashedPassword,
            'country' => null,
            'refferal_id' =>  $referral,
            'image' => null,
            'role' => 'affiliate',
            'market_access' => false,
        ]);

        $email = $validatedData['email'];
        $name = $validatedData['name'];

        try {
            Mail::to($email)->send(new \App\Mail\RegisterSuccess($name));
        } catch (\Exception $e) {
            Log::error('Error sending sale notification to vendor', ['vendor_email' => $email, 'error' => $e->getMessage()]);
        }
        

        // Redirect to signup with success message
        return response()->json([
            'success' => true,
            'message' => 'Registration successful! You can now log in.'
        ]);
    }



    public function handlePaymentCallback(Request $request)
    {
        $orderID = $request->get('orderId');
        $email = urldecode($request->get('email'));
        $reference = $request->get('reference'); // Get reference from the callback

        // Fetch payment details from Paystack
        $paymentDetails = Paystack::getPaymentData();

        // Check if payment was successful
        if ($paymentDetails['data']['status'] === "success") {
            // Retrieve user data from the temporary_users table
            $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

            if (!$temporaryUser) {
                // Notify admin about the issue with registration
                Mail::to('learnflex2@gmail.com')->send(new \App\Mail\IssueOnRegisteration($orderID, $email));

                // Redirect with error message
                return redirect('https://learnerflex.com/auth/signup?status=error&message=' . urlencode('User registration data not found.') . '&email=' . urlencode($email));
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

            // Update the transaction record in the database
            $transaction = Transaction::where('email', $email)->where('transaction_id', $orderID)->latest()->first();
            if ($transaction) {
                $transaction->update([
                    'tx_ref' => $reference,
                    'status' => $paymentDetails['data']['status']
                ]);
            }

            // Delete the temporary user data
            $temporaryUser->delete();

            // Generate token and send confirmation email
            $token = $user->createToken('YourAppName')->plainTextToken;
            $name = $temporaryUser->name;
            Mail::to($email)->send(new \App\Mail\RegisterSuccess($name));

            // Redirect to signup with success message
            return redirect('https://learnerflex.com/auth/signup?status=success&message=' . urlencode('Registration successful! You can now log in.') . '&email=' . urlencode($email));
        } else {
            // Payment failed, retrieve temporary user data to repopulate the registration form
            $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

            if ($temporaryUser) {
                // Redirect with error message and user data
                return redirect('https://learnerflex.com/auth/signup?status=error&message=' . urlencode('Payment failed or incomplete.') . '&email=' . urlencode($email) . '&name=' . urlencode($temporaryUser->name) . '&phone=' . urlencode($temporaryUser->phone));
            }

            // If no temporary user data is found
            return redirect('https://learnerflex.com/auth/signup?status=error&message=' . urlencode('Payment failed or incomplete, and no temporary user data found.') . '&email=' . urlencode($email));
        }
    }



    // for frontend handling the callback request

    // public function handlePaymentCallback(Request $request)
    // {
    //     // Validate the incoming request data
    //     $request->validate([
    //         'orderId' => 'required|string', // Ensure orderId is required and a string
    //         'email' => 'required|email', // Ensure email is required and in a valid email format
    //         'reference' => 'required|string', // Ensure reference is required and a string
    //     ]);

    //     // Retrieve orderID, email, and reference from form data
    //     $orderID = $request->input('orderId');
    //     $email = $request->input('email');
    //     $reference = $request->input('reference');

    //     // Fetch payment details from Paystack
    //     $paymentDetails = json_decode($this->verify_payment($reference));

    //     // Check if the payment details are valid and successful
    //     if ($paymentDetails && $paymentDetails['data']['status'] == "success" && $paymentDetails['data']['reference'] === $reference) {
    //         // Validate if the email matches the one from Paystack
    //         if ($paymentDetails['data']['customer']['email'] !== $email) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Payment verification failed due to mismatched email.'
    //             ], 403);
    //         }

    //         // Retrieve user data from the temporary_users table
    //         $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

    //         if (!$temporaryUser) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'User registration data not found.'
    //             ], 404);
    //         }

    //         // Proceed with user creation and transaction update
    //         do {
    //             $aff_id = Str::random(8);
    //             $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //         } while ($exists);

    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => $temporaryUser->name,
    //             'email' => $temporaryUser->email,
    //             'phone' => $temporaryUser->phone,
    //             'password' => $temporaryUser->password,
    //             'country' => null,
    //             'refferal_id' => null,
    //             'image' => null,
    //             'role' => 'affiliate',
    //             'market_access' => true,
    //         ]);

    //         // Update the transaction record
    //         $transaction = Transaction::where('email', $email)->where('transaction_id', $orderID)->latest()->first();

    //         if ($transaction) {
    //             $transaction->update([
    //                 'tx_ref' => $reference,
    //                 'status' => $paymentDetails['data']['status'],
    //             ]);
    //         }

    //         $temporaryUser->delete();

    //         $token = $user->createToken('YourAppName')->plainTextToken;
    //         Mail::to($email)->send(new \App\Mail\RegisterSuccess());

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Registration successful',
    //             'user' => $user,
    //             'token' => $token,
    //         ], 201);
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Payment verification failed or incomplete.',
    //     ], 400);
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
