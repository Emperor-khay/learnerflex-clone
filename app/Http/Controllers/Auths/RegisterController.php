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
            'name' => 'bail|required|string',
            'email' => 'required|string|email',
            'phone_number' => 'required|string',
            'password' => 'required|string|confirmed',
        ]);
    
        // Hash the password
        $hashedPassword = Hash::make($validate['password']);   
        
        // Generate a unique aff_id for the new user
        do {
            $aff_id = Str::random(20);
            $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
        } while ($exists);

        $phoneExist = User::where('phone', $request->phone_number)->first();

        if($phoneExist){
            return response()->json([
                'success' => false,
                'messsage' => 'Phone number already exist'
            ]);
        }
        
        // Initialize referral_id as null
        $referral_id = null;
    
        // Check if token is present and OTP matches
        if ($request->has('otp') && $request->otp === 'sggd63vx7td3dydg3') {

            // Check if user exists with matching email and otp
             $userExists = User::where('email', $request->email)->where('otp', $request->otp)->first();

            if ($userExists) {

                $userExists->update([
                    'name' => $validate['name'],
                    'phone' => $validate['phone_number'],
                    'password' => $hashedPassword,
                    'refferal_id' => null,
                    'has_paid_onboard' => 1,
                    'otp' => null,
                    'market_access' => 1,    
                ]);
            
                $user = $userExists; 
            } else {
                return response()->json([
                    'message' => 'User not eligible to register',
                    'success' => false
                ], 400);
            }
        } elseif ($request->has('aff_id')) {

            // // Check if aff_id is valid
            $referrer = User::where('aff_id', $request->input('aff_id'))->first();
            if ($referrer) {
                 $referral_id = $referrer->id;
            } else {
                return response()->json([
                     'message' => 'Invalid referral code',
                    'success' => false
                ], 400);
            }
            
            $emailExist = User::where('email', $request->email)->first();

            if($emailExist){
                return response()->json([
                    'success' => false,
                    'messsage' => 'Email already exist'
                ]);
            }
    
            // // Create the new user
            $user = User::create([
                'aff_id' => $aff_id,
                'name' => $validate['name'],
                'email' => $validate['email'],
                'phone' => $validate['phone_number'],
                'password' => $hashedPassword,
                'country' => null,
                'refferal_id' => $referral_id,
                'image' => null,
                'has_paid_onboard' => 1,
                'is_vendor' => 0,
                'vendor_status' => 'down',
                'otp' => null,
                'market_access' => 1,    
            ]);

        } else {
            return response()->json([
                'message' => 'OTP or referral code required',
                'success' => false
            ], 400);
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
 
}
