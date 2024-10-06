<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

    // Bulk upload affiliates from CSV
    public function bulkUpload(Request $request)
    {
        // Validate if the file is uploaded and is a CSV
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        // Read the file
        $file = $request->file('csv_file');
        $csvData = file_get_contents($file->getRealPath());

        // Remove BOM if present
        $csvData = preg_replace('/^\xEF\xBB\xBF/', '', $csvData);

        // Process the CSV data
        $rows = array_map('str_getcsv', explode("\n", $csvData));
        $header = array_shift($rows); // Remove and get the header

        $affiliates = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Check if the row has the same number of columns as the header
            if (count($header) != count($row)) {
                $errors[] = "Row " . ($index + 2) . " does not match header column count.";
                continue; // Skip invalid rows
            }

            // Create associative array using header as keys and row values as data
            $affiliateData = array_combine($header, $row);

            // Generate or retrieve a unique aff_id for the new user
            if (isset($affiliateData['aff_id']) && !empty($affiliateData['aff_id'])) {
                $aff_id = $affiliateData['aff_id'];
            } else {
                do {
                    $aff_id = Str::random(20);
                    $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
                } while ($exists);
            }

            // Validate each row data
            $validator = Validator::make($affiliateData, [
                'name' => 'required|string',
                'email' => 'required|string|email',
                'phone_number' => 'required|string',
                'password' => 'required|string',
                'refferal_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors[] = "Validation failed for row " . ($index + 2) . ": " . json_encode($validator->errors());
                continue;
            }

            // Check if the user exists
            $existingUser = User::where('email', $affiliateData['email'])->first();

            // Prepare data for insert or update
            $hashedPassword = isset($affiliateData['password']) ? Hash::make($affiliateData['password']) : null;

            $ref_id = null;
            if ($affiliateData['refferal_id']) {
                $referrer = User::where('aff_id', $affiliateData['refferal_id'])->first();
                if ($referrer) {
                    $ref_id = $referrer->id;
                }
            }

            // If the user exists, update the user
            if ($existingUser) {
                $existingUser->update([
                    'name' => $affiliateData['name'],
                    'phone' => $affiliateData['phone_number'],
                    'password' => $hashedPassword ? $hashedPassword : $existingUser->password,
                    'refferal_id' => $ref_id,
                ]);
            } else {
                // If the user doesn't exist, create a new user
                $affiliates[] = [
                    'aff_id' => $aff_id,
                    'name' => $affiliateData['name'],
                    'email' => $affiliateData['email'],
                    'phone' => $affiliateData['phone_number'],
                    'password' => $hashedPassword,
                    'country' => null,
                    'refferal_id' => $ref_id,
                    'image' => null,
                    'has_paid_onboard' => 1,
                    'is_vendor' => 0,
                    'vendor_status' => 'down',
                    'otp' => null,
                    'market_access' => 1,
                ];
            }
        }

        // Insert all new valid data into the database
        if (!empty($affiliates)) {
            User::insert($affiliates);
        }

        // Return success message or errors if any
        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some rows failed to upload',
                'errors' => $errors
            ], 422);
        }

        return response()->json([
            'message' => 'Affiliates uploaded successfully',
            'new_records' => count($affiliates),
        ], 201);
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
