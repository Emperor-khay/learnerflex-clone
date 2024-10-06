<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SuperAdminUserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return response()->json($users);
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

    public function store(Request $request)
    {
        $user = User::create($request->all());

        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->update($request->all());
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
