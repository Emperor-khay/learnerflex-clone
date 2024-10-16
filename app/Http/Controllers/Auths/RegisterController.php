<?php

namespace App\Http\Controllers\Auths;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Service\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\VendorStatusEnum;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class RegisterController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validate = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email',
            'phone_number' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);

        // Hash the password
        $hashedPassword = Hash::make($validate['password']);

        // Check for existing phone number
        if (User::where('phone', $request->phone_number)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number already exists'
            ]);
        }

        // Initialize referral_id as null
        $referral_id = null;

        // Check if the user exists with the provided email
        $userExists = User::where('email', $request->email)->first();

        // Check for the case of OTP registration
        if ($userExists) {
            if ($userExists->otp) {
                $transaction = Transaction::where('tx_ref', $userExists->otp)
                    ->where('email', $request->email)
                    ->where('status', 'success')
                    ->first();

                if ($transaction) {
                    $userExists->update([
                        'name' => $validate['name'],
                        'phone' => $request->phone_number,
                        'password' => $hashedPassword,
                        'refferal_id' => null,
                        'has_paid_onboard' => 1,
                        'role' => 'affiliate',
                        'otp' => null,
                        'market_access' => 1,
                    ]);
                    $user = $userExists;
                    // return response()->json(['success' => true, 'message' => 'Registration successful', 'user' => $userExists]);
                } else {
                    return response()->json(['message' => 'Invalid OTP or transaction not successful', 'success' => false], 400);
                }
            } else {
                return response()->json(['message' => 'User not eligible to register; OTP not found', 'success' => false], 400);
            }
        } elseif ($request->has('aff_id')) {
            // Check if aff_id is valid and check for successful transactions
            $referrer = User::where('aff_id', $request->input('aff_id'))->first();
            if ($referrer) {
                $referral_id = $referrer->id;
            } else {
                return response()->json(['message' => 'Invalid referral code', 'success' => false], 400);
            }

            // Check for successful transactions with the provided aff_id
            $transaction = Transaction::where('affiliate_id', $referral_id)
                ->where('email', $request->email) // Check if the new user's email is associated with the transaction
                ->where('status', 'success')
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'Referrer has no successful transactions', 'success' => false], 400);
            }

            // Generate a unique aff_id for the new user
            do {
                $aff_id = Str::random(8);
                $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
            } while ($exists);

            // Create the new user
            $user = User::create([
                'aff_id' => $aff_id,
                'name' => $validate['name'],
                'email' => $validate['email'],
                'phone' => $request->phone_number,
                'password' => $hashedPassword,
                'country' => null,
                'refferal_id' => $referral_id,
                'image' => null,
                'has_paid_onboard' => 1,
                'is_vendor' => 0,
                'vendor_status' => 'down',
                'role' => 'affiliate',
                'otp' => null,
                'market_access' => 0,
            ]);
        } else {
            // Check for transactions based on user email
            $transaction = Transaction::where('email', $request->email)
                ->where('status', 'success')
                ->first();

            if (!$transaction) {
                return response()->json(['message' => 'No successful transactions found for this email', 'success' => false], 400);
            }

            // Generate a unique aff_id for the new user
            do {
                $aff_id = Str::random(20);
                $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
            } while ($exists);

            // Create the new user
            $user = User::create([
                'aff_id' => $aff_id,
                'name' => $validate['name'],
                'email' => $validate['email'],
                'phone' => $request->phone_number,
                'password' => $hashedPassword,
                'country' => null,
                'refferal_id' => null, // Set to null if no referrer found
                'image' => null,
                'has_paid_onboard' => 1,
                'is_vendor' => 0,
                'vendor_status' => 'down',
                'role' => 'affiliate',
                'otp' => null,
                'market_access' => 0,
            ]);
        }

        // Generate token and send confirmation email
        $token = $user->createToken('YourAppName')->plainTextToken;
        Mail::to($validate['email'])->send(new \App\Mail\RegisterSuccess());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

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

    //     $phoneExist = User::where('phone', $request->phone_number)->first();

    //     if ($phoneExist) {
    //         return response()->json([
    //             'success' => false,
    //             'messsage' => 'Phone number already exist'
    //         ]);
    //     }

    //     // Initialize referral_id as null
    //     $referral_id = null;

    //     // Check if the user exists with the provided email
    //     $userExists = User::where('email', $request->email)->first();

    //     if ($userExists) {
    //         // Check if the user has a stored OTP
    //         if ($userExists->otp) {
    //             // Check if the stored OTP matches a successful transaction reference
    //             $transaction = Transaction::where('tx_ref', $userExists->otp)
    //                 ->where('email', $request->email)
    //                 ->where('status', 'success') // Ensure the transaction was successful
    //                 ->first();

    //             if ($transaction) {
    //                 // Update the user details
    //                 $userExists->update([
    //                     'name' => $validate['name'],
    //                     'phone' => $validate['phone_number'],
    //                     'password' => $hashedPassword,
    //                     'refferal_id' => null,
    //                     'has_paid_onboard' => 1,
    //                     'role' => 'affiliate',
    //                     'otp' => null, // Clear OTP after registration
    //                     'market_access' => 1,
    //                 ]);

    //                 $user = $userExists;
    //             } else {
    //                 return response()->json([
    //                     'message' => 'Invalid OTP or transaction not successful',
    //                     'success' => false
    //                 ], 400);
    //             }
    //         } else {
    //             return response()->json([
    //                 'message' => 'User not eligible to register; OTP not found',
    //                 'success' => false
    //             ], 400);
    //         }
    //     } elseif ($request->has('aff_id')) {

    //         // // Check if aff_id is valid
    //         $referrer = User::where('aff_id', $request->input('aff_id'))->first();
    //         if ($referrer) {
    //             $referral_id = $referrer->id;
    //         } else {
    //             return response()->json([
    //                 'message' => 'Invalid referral code',
    //                 'success' => false
    //             ], 400);
    //         }
    //          // Generate a unique aff_id for the new user
    //     do {
    //         $aff_id = Str::random(20);
    //         $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
    //     } while ($exists);


    //         // // Create the new user
    //         $user = User::create([
    //             'aff_id' => $aff_id,
    //             'name' => $validate['name'],
    //             'email' => $validate['email'],
    //             'phone' => $validate['phone_number'],
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
    //         return response()->json([
    //             'message' => 'OTP or referral code required',
    //             'success' => false
    //         ], 400);
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
}
