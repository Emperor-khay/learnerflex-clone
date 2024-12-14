<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Affiliate;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\VendorStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\VendorAccountRejected;
use App\Mail\VendorAccountUpgraded;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SuperAdminUserController extends Controller
{
    public function index(Request $request)
    {
        // Get the 'per_page' query parameter or default to 20
        $perPage = $request->get('per_page', 20);

        // Get the 'role' query parameter to filter users by role (if provided)
        $role = $request->get('role');

        // Get the 'name' query parameter to filter users by name (if provided)
        $searchName = $request->get('name');

        // Build the query for users
        $query = User::query();

        // If a role is provided, filter the users by their role
        if ($role) {
            $query->where('role', $role);
        }

        // If a name is provided, filter users by a simple search (case-insensitive)
        if ($searchName) {
            $query->where('name', 'LIKE', '%' . $searchName . '%');
        }

        // Paginate users based on the query parameters
        $users = $query->paginate($perPage);

        // Retrieve the count of users by role
        $roleCounts = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role');

        // Retrieve the count of pending vendor statuses
        $pendingVendorCount = VendorStatus::where('status', 'pending')->count();

        // Return a JSON response with users, pagination metadata, role counts, and pending vendor count
        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'role_counts' => $roleCounts,
            'pending_vendor_count' => $pendingVendorCount,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ]);
    }





    public function show($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $vendor = Vendor::where('user_id', $userId)->first();

            $products = Product::where('user_id', $userId)->get();

            $transactions = Transaction::where('user_id', $userId)->get();

            $referrer = null;
            if ($user->refferal_id) {
                $referrer = User::where('id', $user->refferal_id)->select('name')->first();
            }

            return response()->json([
                'user' => $user,
                'vendor' => $vendor,
                'products' => $products,
                'transactions' => $transactions,
                'referrer' => $referrer ? $referrer->name : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data: ' . $e->getMessage()], 500);
        }
    }

    public function getReferrerByReferralId($referralId)
    {
        $referrer = User::where('id', $referralId)->select('name')->first();

        if (!$referrer) {
            return response()->json(['message' => 'No referrer found'], 404);
        }

        return response()->json($referrer);
    }

    public function showUser($id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id);

            // Determine the relationships to load dynamically
            $relations = [];

            if ($user->role === 'vendor') {
                $relations[] = 'products';
            }

            $relations[] = 'vendor'; // Always include vendors if it exists

            // Load the specified relationships
            $user->load($relations);

            // Return the result as JSON
            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            // Handle errors gracefully
            return response()->json([
                'error' => 'An error occurred while retrieving the user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function createUser(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:affiliate,vendor', // Role must be provided and valid
            'vendor_email' => 'nullable|email|exists:users,email', // Vendor email is optional
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            // Generate a unique aff_id for the new user
            $aff_id = null;
            do {
                $aff_id = Str::random(8);
                $exists = DB::table('users')->where('aff_id', $aff_id)->exists();
            } while ($exists);

            // Create the new user
            $user = User::create([
                'aff_id' => $aff_id,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'currency' => $request->input('currency', 'NGN'), // Default to 'NGN' if not provided
                'referral_id' => null, // Can be updated later if needed
                'role' => $request->input('role'),
            ]);

            // Handle vendor_email if provided
            if ($request->filled('vendor_email')) {
                $vendor = User::where('email', $request->input('vendor_email'))->where('role', 'vendor')->first();
                if (!$vendor) {
                    return response()->json(['error' => 'Vendor not found'], 404);
                }
            }

            // Create a transaction for this vendor-user relationship
            Transaction::create([
                'is_onboarded' => 1,
                'vendor_id' => $vendor->id ?? null,
                'email' => $request->input('email'),
                'amount' => 0,
                'status' => 'success',
            ]);

            DB::commit();

            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:affiliate,vendor', // Role must match valid options
            'vendor_email' => 'nullable|email|exists:users,email', // Vendor email is optional
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            // Find the user
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Update user details
            $user->update($request->only(['name', 'role']));

            // Handle vendor_email if provided
            if ($request->filled('vendor_email')) {
                $vendor = User::where('email', $request->input('vendor_email'))->where('role', 'vendor')->first();
                if (!$vendor) {
                    return response()->json(['error' => 'Vendor not found'], 404);
                }

                // Create a transaction for this vendor-user relationship
                Transaction::create([
                    'is_onboarded' => true,
                    'vendor_id' => $vendor->id,
                    'email' => $user->email,
                    'amount' => 0,
                    'status' => 'success',

                ]);
            }

            DB::commit();

            return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function filterVendorStatus(Request $request)
    {
        // Get the 'status' query parameter to filter vendors
        $status = $request->get('status', 'pending'); // Default to 'pending'

        // Validate the status parameter
        $validStatuses = ['pending', 'inactive', 'active'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status provided.'], 400);
        }

        // Query vendor statuses by the provided status
        $vendors = VendorStatus::with('user')
            ->where('status', $status)
            ->paginate(20); // Default pagination

        return response()->json([
            'success' => true,
            'data' => $vendors->items(),
            'pagination' => [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'total' => $vendors->total(),
                'per_page' => $vendors->perPage(),
            ]
        ]);
    }



    public function deleteUser(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Find the user by ID
            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Check if the user has an associated vendor record
            $vendor = Vendor::where('user_id', $id)->first();

            if ($vendor) {
                // Delete the vendor record (this will also delete the photo due to the boot method in Vendor model)
                $vendor->delete();
            }

            // Optional: Check if the user has products not associated with a vendor
            $products = Product::where('user_id', $id)->get(); // Assuming `Product` model exists

            if ($products->isNotEmpty()) {
                foreach ($products as $product) {
                    $product->delete();
                }
            }

            // Delete the user
            $user->delete();

            DB::commit();

            return response()->json(['message' => 'User, their vendor record, and associated products deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function upgradeAffiliateToVendor(Request $request, $id)
    {
        // Validate the request input
        $request->validate([
            'action' => 'required|in:approve,reject',  // Validate action (approve or reject)
            'comment' => 'nullable|string|max:1000',    // Admin comment is optional for rejection
        ]);

        $action = $request->input('action'); // Get the action (approve or reject) from the request body
        $comment = $request->input('comment'); // Get the comment if provided

        // Find the user by ID
        $user = User::findOrFail($id);

        // Prevent upgrading if user is already a vendor
        if ($action === 'approve' && $user->role === "vendor") {
            return response()->json(['error' => 'User is already a vendor!'], 422);
        }

        try {
            if ($action === 'approve') {
                DB::transaction(function () use ($user) {
                    // Update the user's details to reflect vendor status
                    $user->update([
                        'role' => 'vendor',
                    ]);

                    // Update vendor_status table to mark the user as active
                    DB::table('vendor_status')
                        ->updateOrInsert(
                            ['user_id' => $user->id],
                            [
                                'status' => 'active',
                                'review' => null, // Clear any previous review comments
                                'updated_at' => now(),
                            ]
                        );

                    // Send email notification for success
                    Mail::to($user->email)->send(new VendorAccountUpgraded($user));
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Affiliate upgraded to vendor successfully',
                    'user' => $user->refresh(),
                ], 200);
            } elseif ($action === 'reject') {
                DB::transaction(function () use ($user, $comment) {
                    // Update vendor_status table to include rejection and comment
                    DB::table('vendor_status')
                        ->updateOrInsert(
                            ['user_id' => $user->id],
                            [
                                'status' => 'inactive',
                                'review' => $comment, // Save the admin's comment for rejection
                                'updated_at' => now(),
                            ]
                        );

                    // Send email notification for rejection
                    Mail::to($user->email)->send(new VendorAccountRejected($user, $comment));
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Affiliate vendor request rejected successfully',
                    'user' => $user->refresh(),
                ], 200);
            }

            return response()->json(['error' => 'Invalid action'], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update affiliate vendor status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function requestToBeVendor(Request $request)
    {
        // Validate the filter for status, if provided
        $request->validate([
            'status' => ['nullable', 'in:active,inactive,pending']
        ]);

        // Build the query to fetch vendor requests with user details, optionally filtering by status
        $query = VendorStatus::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Paginate the results
        $paginatedRequests = $query->paginate(20); // Adjust the per-page limit as needed

        return response()->json([
            'success' => true,
            'data' => $paginatedRequests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'user' => [
                        'id' => $request->user->id,
                        'name' => $request->user->name,
                        'email' => $request->user->email,
                    ],
                    'sale_url' => $request->sale_url,
                    'description' => $request->description,
                    'review' => $request->review,
                    'status' => $request->status,
                    'created_at' => $request->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $paginatedRequests->currentPage(),
                'last_page' => $paginatedRequests->lastPage(),
                'total' => $paginatedRequests->total(),
                'per_page' => $paginatedRequests->perPage(),
            ]
        ]);
    }
}
