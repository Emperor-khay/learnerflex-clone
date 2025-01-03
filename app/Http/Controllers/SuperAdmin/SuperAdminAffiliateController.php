<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\TransactionDescription;
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
            try {
                $data = array_combine($header, $row); // Map the header to each row data

                // Check if email and name are present in the row
                if (!isset($data['email']) || !isset($data['name'])) {
                    throw new \Exception('Required email or name is missing in the row.');
                }

                // Check if the email already exists
                $existingUser = User::where('email', $data['email'])->first();

                if ($existingUser) {
                    // Record a transaction for the existing user
                    Transaction::create([
                        'is_onboarded' => 1,
                        'vendor_id' => $vendor->id,
                        'email' => $data['email'],
                        'amount' => 0,
                        'description' => TransactionDescription::POST_ONBOARD->value,
                        'status' => 'success',
                    ]);

                    // Add to skipped emails since the user was not created
                    $skippedEmails[] = $data['email'];
                    continue; // Skip to the next row
                }

                $aff_id = null;
                // Generate a unique aff_id for the new user
                do {
                    $aff_id = Str::random(8);
                    $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
                } while ($exists);

                // Create a new affiliate user with the vendor's user_id as referral_id
                $newUser = User::create([
                    'aff_id' => $aff_id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt('123456'),
                    'currency' => $data['currency'] ?? 'NGN', // Default to 'NGN' if not provided
                    'referral_id' => $vendor->aff_id,
                    'role' => 'affiliate',
                ]);

                // Record a transaction for the newly created user
                Transaction::create([
                    'is_onboarded' => 1,
                    'vendor_id' => $vendor->id,
                    'email' => $data['email'],
                    'amount' => 0,
                    'description' => TransactionDescription::IS_ONBOARDED->value,
                    'status' => 'success',
                ]);

                // Send an email to the newly created user
                    Mail::to($data['email'])->send(new \App\Mail\AffiliateAccountCreated($data['name'], $data['email'], $vendor->name));
                
            } catch (\Exception $e) {
                \Log::error('Error processing row: ' . json_encode($row) . ' - ' . $e->getMessage());
            }
        }

        fclose($file); // Close the file after reading

        // If there are skipped emails, send them to the admin email
        if (!empty($skippedEmails)) {
            try {
                Mail::to('learnerflexltd@gmail.com')->send(new \App\Mail\SkippedEmails($skippedEmails));
            } catch (\Exception $e) {
                \Log::error('Failed to send skipped emails to admin: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Affiliates uploaded successfully'], 200);
    }
}
