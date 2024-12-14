<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Affiliate;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SuperAdminAffiliateController extends Controller
{
    // Get all affiliates
    public function index()
    {
        $affiliates = Affiliate::all();
        return response()->json($affiliates);
    }

    // Get a single affiliate
    public function show($id)
    {
        $affiliate = Affiliate::find($id);

        if (!$affiliate) {
            return response()->json(['error' => 'Affiliate not found'], 404);
        }

        return response()->json($affiliate);
    }

    // Create single affiliate
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'bail|required|string',
            'email' => 'required|string|email|unique:users,email',
            'phone_number' => 'required|string',
            'password' => 'required|string',
            'refferal_id' => 'nullable|string',
        ]);

        $ref_id = null;
        if ($validatedData['refferal_id']) {
            $referrer = User::where('aff_id', $validatedData['refferal_id'])->first();
            if ($referrer) {
                $ref_id = $referrer->id;
            }
        }

        // Hash the password
        $hashedPassword = Hash::make($validatedData['password']);     
    
        // Generate a unique aff_id for the new user
        do {
            $aff_id = Str::random(20);
            $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
        } while ($exists);

        // Create the new user
        $affiliate = User::create([
            'aff_id' => $aff_id,
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone_number'],
            'password' => $hashedPassword,
            'country' => null,
            'refferal_id' => $ref_id,
            'image' => null,
            'has_paid_onboard' => 1,
            'is_vendor' => 0,
            'vendor_status' => 'down',
            'otp' => null,
            'market_access' => 1,
        ]);

        return response()->json(['message' => 'Affiliate created successfully', 'affiliate' => $affiliate], 201);
    }

    //     public function bulkUpload(Request $request)
    // {
    //     // Validate incoming request to ensure a file and vendor email is provided
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file|mimes:csv,txt',
    //         'vendor_email' => 'required|email',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     // Search for the vendor using the provided email
    //     $vendor = User::where('email', $request->input('vendor_email'))->where('role', 'vendor')->first();
    //     if (!$vendor) {
    //         return response()->json(['error' => 'Vendor not found'], 404);
    //     }

    //     // Open and read the CSV file
    //     $file = fopen($request->file('file'), 'r');
    //     $header = fgetcsv($file); // Read the header

    //     // Begin database transaction
    //     DB::beginTransaction();
        
    //     try {
    //         // Loop through the CSV rows
    //         while ($row = fgetcsv($file)) {
    //             $data = array_combine($header, $row); // Map the header to each row data

    //             // Check if email and name are present in the row
    //             if (!isset($data['email']) || !isset($data['name'])) {
    //                 continue; // Skip row if required data is missing
    //             }

    //             // Check if the user with the email already exists to avoid duplication
    //             if (User::where('email', $data['email'])->exists()) {
    //                 continue; // Skip row if email already exists
    //             }

    //             $aff_id = null;
    //             // Generate a unique aff_id for the new user within the transaction
    //             do {
    //                 $aff_id = substr(Str::uuid7()->toString(), 0, 12);
    //             } while (User::where('aff_id', $aff_id)->exists());

    //             // Create a new affiliate user with the vendor's user_id as referral_id
    //             User::create([
    //                 'aff_id' => $aff_id,
    //                 'name' => $data['name'],
    //                 'email' => $data['email'],
    //                 'currency' => $data['currency'] ?? 'NGN', // Default to 'NGN' if not provided
    //                 'referral_id' => $vendor->id,
    //                 'role' => 'affiliate',
    //             ]);
    //         }

    //         // Commit the transaction if everything is fine
    //         DB::commit();
    //     } catch (\Exception $e) {
    //         // Rollback the transaction on error
    //         DB::rollBack();
    //         return response()->json(['error' => 'An error occurred during upload: ' . $e->getMessage()], 500);
    //     } finally {
    //         fclose($file); // Close the file after reading
    //     }

    //     return response()->json(['message' => 'Affiliates uploaded successfully'], 200);
    // }

    public function bulkUpload(Request $request)
{
    // Validate incoming request to ensure a file and vendor email is provided
    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:csv,txt',
        'vendor_email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Search for the vendor using the provided email
    $vendor = User::where('email', $request->input('vendor_email'))->where('role', 'vendor')->first();
    if (!$vendor) {
        return response()->json(['error' => 'Vendor not found'], 404);
    }

    // Open and read the CSV file
    $file = fopen($request->file('file'), 'r');
    $header = fgetcsv($file); // Read the header

    $skippedEmails = []; // Store skipped emails

    // Loop through the CSV rows
    while ($row = fgetcsv($file)) {
        $data = array_combine($header, $row); // Map the header to each row data

        // Check if email and name are present in the row
        if (!isset($data['email']) || !isset($data['name'])) {
            continue; // Skip row if required data is missing
        }

        // Check if the email already exists
        if (User::where('email', $data['email'])->exists()) {
            // Add to skipped emails if the email already exists
            $skippedEmails[] = $data['email'];
            continue; // Skip this record
        }

        $aff_id = null;
         // Generate a unique aff_id for the new user
         do {
            $aff_id = Str::random(8);
            $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
        } while ($exists);

        // Create a new affiliate user with the vendor's user_id as referral_id
        User::create([
            'aff_id' => $aff_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'currency' => $data['currency'] ?? 'NGN', // Default to 'NGN' if not provided
            'referral_id' => $vendor->aff_id,
            'role' => 'affiliate',
        ]);

        Transaction::create([
            'is_onboarded' => true,
            'vendor_id' => $vendor->id, // Ensure $vendorId contains the appropriate vendor ID
        ]);
        
    }

    fclose($file); // Close the file after reading

    // If there are skipped emails, send them to the admin email
    if (!empty($skippedEmails)) {
        Mail::to('admin1@gmail.com')->send(new \App\Mail\SkippedEmails($skippedEmails));
    }

    return response()->json(['message' => 'Affiliates uploaded successfully'], 200);
}




    // Update an affiliate
    public function update(Request $request, $id)
    {
        $affiliate = Affiliate::find($id);

        if (!$affiliate) {
            return response()->json(['error' => 'Affiliate not found'], 404);
        }

        $affiliate->update($request->all());

        return response()->json(['message' => 'Affiliate updated successfully', 'affiliate' => $affiliate]);
    }
}
