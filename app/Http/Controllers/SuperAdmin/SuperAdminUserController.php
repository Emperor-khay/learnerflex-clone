<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Affiliate;
use App\Models\Transaction;
use App\Models\VendorStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\VendorAccountRejected;
use App\Mail\VendorAccountUpgraded;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class SuperAdminUserController extends Controller
{
    public function index(Request $request)
    {
        // Get the 'per_page' query parameter or default to 10
        $perPage = $request->get('per_page', 20); // Default is 20 users per page

        // Get the 'role' query parameter to filter users by role (if provided)
        $role = $request->get('role');

        // Build the query for users
        $query = User::query();

        // If a role is provided, filter the users by their role
        if ($role) {
            $query->where('role', $role); // Assuming 'role' is a column in the users table
        }

        // Paginate users based on the query parameters
        $users = $query->paginate($perPage);

        // Return a JSON response with pagination metadata
        return response()->json([
            'success' => true,
            'data' => $users->items(), // List of users
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

    public function showuser($role, $id)
    {
        switch ($role) {
            case 'admin':
                $entity = User::findOrFail($id);
                break;
            case 'vendor':
                $entity = User::with('products', $id)->firstOrFail($id);
                break;
            case 'affiliate':
                $entity = User::firstOrFail($id);
                break;
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }

        return response()->json($entity);
    }

    public function store(Request $request, $role)
    {
        switch ($role) {
            case 'vendor':
                $vendor = Vendor::create($request->all());
                return response()->json($vendor, 201);
            case 'affiliate':
                $affiliate = Affiliate::create($request->all());
                return response()->json($affiliate, 201);
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }
    }

    public function update(Request $request, $role, $id)
    {
        switch ($role) {
            case 'user':
                $user = User::findOrFail($id);
                $user->update($request->all());
                return response()->json($user);
            case 'vendor':
                $vendor = Vendor::where('user_id', $id)->firstOrFail();
                $vendor->update($request->all());
                return response()->json($vendor);
            case 'affiliate':
                $affiliate = Affiliate::where('user_id', $id)->firstOrFail();
                $affiliate->update($request->all());
                return response()->json($affiliate);
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }
    }

    public function destroy($role, $id)
    {
        switch ($role) {
            case 'user':
                User::findOrFail($id)->delete();
                return response()->json(['message' => 'User deleted']);
            case 'vendor':
                Vendor::where('user_id', $id)->firstOrFail()->delete();
                return response()->json(['message' => 'Vendor deleted']);
            case 'affiliate':
                Affiliate::where('user_id', $id)->firstOrFail()->delete();
                return response()->json(['message' => 'Affiliate deleted']);
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }
    }

    public function updateAffiliateVendorStatus(Request $request, $id)
    {
        // Validate the request input
        $request->validate([
            'action' => 'required|in:approve,reject',  // Validate action (approve or reject)
            'comment' => 'nullable|string|max:500',    // Admin comment is optional for rejection
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
