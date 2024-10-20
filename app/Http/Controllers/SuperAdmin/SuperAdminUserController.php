<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Affiliate;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                $entity = Vendor::where('user_id', $id)->firstOrFail();
                break;
            case 'affiliate':
                $entity = Affiliate::where('user_id', $id)->firstOrFail();
                break;
            default:
                return response()->json(['error' => 'Invalid role'], 400);
        }

        return response()->json($entity);
    }

    public function store(Request $request, $role)
    {
        switch ($role) {
            case 'user':
                $user = User::create($request->all());
                return response()->json($user, 201);
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

    public function upgradeAffiliateToVendor($id)
    {
        // Find the user by ID
        $user = User::findOrFail($id);

        // Check if the user is already a vendor
        if ($user->is_vendor && $user->role == "vendor") {
            return response()->json(['error' => 'User is already a vendor!'], 422);
        }

        try {
            DB::transaction(function () use ($user) {
                // Update the user's details to reflect vendor status
                $user->update([
                    'is_vendor' => true,
                    'vendor_status' => 'up', // Adjust according to your enum values
                    'role' => 'vendor',
                ]);

                // Update the vendor_status table for the user
                DB::table('vendor_status')->where('user_id', $user->id)->updateOrInsert(
                    ['user_id' => $user->id],
                    ['status' => 'active', 'updated_at' => now()]
                );

                // Send email notification to the affiliate
                Mail::to($user->email)->send(new VendorAccountUpgraded($user));
            });

            return response()->json([
                'success' => true,
                'message' => 'Affiliate upgraded to vendor successfully',
                'user' => $user->refresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upgrade affiliate to vendor',
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

        // Fetch all vendor requests, optionally filtering by status
        $query = DB::table('vendor_status')->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->input('status') !== null) {
            $query->where('status', $request->input('status'));
        }

        // Paginate the results
        $paginatedRequests = $query->paginate(20); // Adjust the per-page limit as needed

        return response()->json([
            'success' => true,
            'data' => $paginatedRequests->items(),
            'pagination' => [
                'current_page' => $paginatedRequests->currentPage(),
                'last_page' => $paginatedRequests->lastPage(),
                'total' => $paginatedRequests->total(),
                'per_page' => $paginatedRequests->perPage(),
            ]
        ]);
    }
}
