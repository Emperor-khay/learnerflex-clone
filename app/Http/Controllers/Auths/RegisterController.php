<?php

namespace App\Http\Controllers\Auths;

use Log;
use App\Models\User;
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
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Notifications\Notification;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Notifications\RegistrationIssueNotification;


class RegisterController extends Controller
{
    // public function store(Request $request)
    // {
    //     // Validate the incoming request data
    //     $validate = $request->validate([
    //         'name' => 'required|string',
    //         'email' => 'required|string|email',
    //         'phone_number' => 'required|string',
    //         'password' => 'required|string|confirmed',
    //     ]);

    //     // Hash the password
    //     $hashedPassword = Hash::make($validate['password']);

    //     // Check for existing phone number
    //     if (User::where('phone', $request->phone_number)->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Phone number already exists'
    //         ]);
    //     }

    //     // Initialize referral_id as null
    //     $referral_id = null;

    //     // Check if the user exists with the provided email
    //     $userExists = User::where('email', $request->email)->first();

    //     // Check for the case of OTP registration
    //     if ($userExists) {
    //         if ($userExists->otp) {
    //             $transaction = Transaction::where('tx_ref', $userExists->otp)
    //                 ->where('email', $request->email)
    //                 ->where('status', 'success')
    //                 ->first();

    //             if ($transaction) {
    //                 $userExists->update([
    //                     'name' => $validate['name'],
    //                     'phone' => $request->phone_number,
    //                     'password' => $hashedPassword,
    //                     'refferal_id' => null,
    //                     'has_paid_onboard' => 1,
    //                     'role' => 'affiliate',
    //                     'otp' => null,
    //                     'market_access' => 1,
    //                 ]);
    //                 $user = $userExists;
    //                 // return response()->json(['success' => true, 'message' => 'Registration successful', 'user' => $userExists]);
    //             } else {
    //                 return response()->json(['message' => 'Invalid OTP or transaction not successful', 'success' => false], 400);
    //             }
    //         } else {
    //             return response()->json(['message' => 'User not eligible to register; OTP not found', 'success' => false], 400);
    //         }
    //     } elseif ($request->has('aff_id')) {
    //         // Check if aff_id is valid and check for successful transactions
    //         $referrer = User::where('aff_id', $request->input('aff_id'))->first();
    //         if ($referrer) {
    //             $referral_id = $referrer->id;
    //         } else {
    //             return response()->json(['message' => 'Invalid referral code', 'success' => false], 400);
    //         }

    //         // Check for successful transactions with the provided aff_id
    //         $transaction = Transaction::where('affiliate_id', $referral_id)
    //             ->where('email', $request->email) // Check if the new user's email is associated with the transaction
    //             ->where('status', 'success')
    //             ->first();

    //         if (!$transaction) {
    //             return response()->json(['message' => 'Referrer has no successful transactions', 'success' => false], 400);
    //         }

    //         // Generate a unique aff_id for the new user
    //         do {
    //             $aff_id = Str::random(8);
    //             $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //         } while ($exists);

    //         // Create the new user
    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => $validate['name'],
    //             'email' => $validate['email'],
    //             'phone' => $request->phone_number,
    //             'password' => $hashedPassword,
    //             'country' => null,
    //             'refferal_id' => $referral_id,
    //             'image' => null,
    //             'has_paid_onboard' => 1,
    //             'is_vendor' => 0,
    //             'vendor_status' => 'down',
    //             'role' => 'affiliate',
    //             'otp' => null,
    //             'market_access' => 0,
    //         ]);
    //     } else {
    //         // Check for transactions based on user email
    //         $transaction = Transaction::where('email', $request->email)
    //             ->where('status', 'success')
    //             ->first();

    //         if (!$transaction) {
    //             return response()->json(['message' => 'No successful transactions found for this email', 'success' => false], 400);
    //         }

    //         // Generate a unique aff_id for the new user
    //         do {
    //             $aff_id = Str::random(20);
    //             $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //         } while ($exists);

    //         // Create the new user
    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => $validate['name'],
    //             'email' => $validate['email'],
    //             'phone' => $request->phone_number,
    //             'password' => $hashedPassword,
    //             'country' => null,
    //             'refferal_id' => null, // Set to null if no referrer found
    //             'image' => null,
    //             'has_paid_onboard' => 1,
    //             'is_vendor' => 0,
    //             'vendor_status' => 'down',
    //             'role' => 'affiliate',
    //             'otp' => null,
    //             'market_access' => 0,
    //         ]);
    //     }

    //     // Generate token and send confirmation email
    //     $token = $user->createToken('YourAppName')->plainTextToken;
    //     Mail::to($validate['email'])->send(new \App\Mail\RegisterSuccess());

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Registration successful',
    //         'user' => $user,
    //         'token' => $token,
    //     ], 201);
    // }

    public function initiateRegistration(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users', // Changed to `phone`
            'password' => 'required|string|confirmed|min:4',
            'aff_id' => 'nullable|string|max:30', // aff_id is optional
        ]);


        // If aff_id is present, register the user directly
        if ($request->filled('aff_id')) {
            $this->storeUser($validatedData, $request->aff_id);
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
                TemporaryUsers::create([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'phone' => $validatedData['phone'],
                    'password' => $hashedPassword, // Store hashed password
                    'aff_id' => null,
                    'order_id' => $orderID,
                ]);

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
                    'description' => 'Registeration fee',
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
        // Hash the password
        $hashedPassword = Hash::make($validatedData['password']);

        $aff_id = null;
        // Generate a unique aff_id for the new user
        do {
            $aff_id = Str::random(8);
            $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
        } while ($exists);
        // Create the new user
        $user = User::create([
            'aff_id' => $aff_id,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone_number'],
            'password' => $hashedPassword,
            'country' => null,
            'refferal_id' =>  $referral,
            'image' => null,
            'role' => 'affiliate',
            'market_access' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Registration successful', 'user' => $user]);
    }

    // public function handlePaymentCallback(Request $request)
    // {
    //     $orderID = $request->get('orderId');
    //     $email = urldecode($request->get('email'));
    //     $reference = request('reference'); // Get reference from the callback

    //     // Fetch payment details from Paystack
    //     $paymentDetails = Paystack::getPaymentData();

    //     // Check if payment was successful
    //     if ($paymentDetails['data']['status'] == "success") {

    //         // Retrieve user data from the temporary_users table
    //         $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id',  $orderID)->first();

    //         if (!$temporaryUser) {
    //             // Notify admin about the issue with registration
    //             // Notification::send(
    //             //     User::where('role', 'admin')->get(), // Assuming admins are marked with 'role' as 'admin'
    //             //     new RegistrationIssueNotification($email, $orderID) // Create a notification class
    //             // );

    //             return redirect()->route('auth.login')->withErrors([
    //                 'message' => 'User registration data not found.',
    //             ]);
    //         }

    //         // Generate a unique aff_id for the new user
    //         do {
    //             $aff_id = Str::random(8);
    //             $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //         } while ($exists);

    //         // Create the new user
    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => $temporaryUser->name,
    //             'email' => $temporaryUser->email,
    //             'phone' => $temporaryUser->phone,
    //             'password' => $temporaryUser->password, // Already hashed
    //             'country' => null,
    //             'refferal_id' => null,
    //             'image' => null,
    //             'role' => 'affiliate',
    //             'market_access' => true,
    //         ]);

    //         // Update the transaction record in the database
    //         $transaction = Transaction::where('email', $email)->where('transaction_id', $orderID)->latest()->first();

    //         if ($transaction) {
    //             $transaction->update([
    //                 'tx_ref' => $reference,
    //                 'status' => $paymentDetails['data']['status'],
    //             ]);
    //         }

    //         // Delete the temporary user data
    //         $temporaryUser->delete();

    //         // Redirect the user to the login page with a success message and their email
    //         // return redirect('/')->with('success', 'Registration successful! You can now log in.');
    //         // Generate token and send confirmation email
    //         $token = $user->createToken('YourAppName')->plainTextToken;
    //         Mail::to($email)->send(new \App\Mail\RegisterSuccess());

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Registration successful',
    //             'user' => $user,
    //             'token' => $token,
    //         ], 201);
    //     } else {
    //         // Payment failed, retrieve temporary user data to repopulate the registration form
    //         $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

    //         if ($temporaryUser) {
    //             // Pass temporary user data back to the frontend to repopulate the form
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Payment failed or incomplete.',
    //                 'status' => $paymentDetails['data']['status'],
    //                 'formData' => [
    //                     'name' => $temporaryUser->name,
    //                     'email' => $temporaryUser->email,
    //                     'phone' => $temporaryUser->phone,
    //                     // Add any other fields that need to be repopulated
    //                 ],
    //             ], 400);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Payment failed or incomplete, and no temporary user data found.',
    //             'status' => $paymentDetails['data']['status'],
    //         ], 400);
    //     }
    // }

    public function handlePaymentCallback(Request $request)
    {
        $orderID = $request->get('orderId');
        $email = urldecode($request->get('email'));
        $reference = request('reference'); // Get reference from the callback

        // Fetch payment details from Paystack
        $paymentDetails = Paystack::getPaymentData();

        // Check if payment was successful
        if ($paymentDetails['data']['status'] == "success") {
            // Retrieve user data from the temporary_users table
            $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

            if (!$temporaryUser) {
                // Notify admin about the issue with registration
                // Notification::send(
                //     User::where('role', 'admin')->get(), // Assuming admins are marked with 'role' as 'admin'
                //     new RegistrationIssueNotification($email, $orderID) // Create a notification class
                // );

                // Redirect to login with error message as query parameters
                return redirect('/transaction-status?status=error&message=' . urlencode('User registration data not found.') . '&email=' . urlencode($email));
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
                    'status' => $paymentDetails['data']['status'],
                ]);
            }

            // Delete the temporary user data
            $temporaryUser->delete();

            // Generate token and send confirmation email
            $token = $user->createToken('YourAppName')->plainTextToken;
            Mail::to($email)->send(new \App\Mail\RegisterSuccess());

            // Redirect the user to the transaction-status page with a success message
            return redirect('/transaction-status?status=success&message=' . urlencode('Registration successful! You can now log in.') . '&email=' . urlencode($email));
        } else {
            // Payment failed, retrieve temporary user data to repopulate the registration form
            $temporaryUser = TemporaryUsers::where('email', $email)->where('order_id', $orderID)->first();

            if ($temporaryUser) {
                // Redirect back with error message and user data as query parameters to repopulate the registration form
                return redirect('/transaction-status?status=error&message=' . urlencode('Payment failed or incomplete.') . '&email=' . urlencode($email) . '&name=' . urlencode($temporaryUser->name) . '&phone=' . urlencode($temporaryUser->phone));
            }

            // If no temporary user data is found
            return redirect('/transaction-status?status=error&message=' . urlencode('Payment failed or incomplete, and no temporary user data found.') . '&email=' . urlencode($email));
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
