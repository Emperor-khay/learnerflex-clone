<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Earning;
use App\Models\Product;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Flutterwave\Service\Transactions;

class SecondVendorController extends Controller
{

    public function vendorDashboardMetrics(Request $request)
    {
        try {
            // Get authenticated vendor
            $vendor = Auth::guard('sanctum')->user();

            if (!$vendor) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Optional date filters for metrics
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

            // 4. Total Withdrawals (Sum all withdrawals for the vendor)
            $totalWithdrawals = Withdrawal::where('user_id', $vendor->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available Vendor Earnings (Total earnings for the vendor)
            $availableEarn = Transaction::where('vendor_id', $vendor->id) // Query the transactions table
                ->where('status', 'success') // Transaction must be successful
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');  // Sum of the amount from transactions

            // Calculate available earnings
            $availableEarnings = $availableEarn - $totalWithdrawals;

            // 2. Today's Vendor Sales (Sales with vendor for the current day - both count and amount)
            $todaySalesData = Transaction::where('vendor_id', $vendor->id)
                ->where('status', 'success') // Query transactions for successful sales
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total Vendor Sales (All-time or filtered by date sales with vendor - both count and amount)
            $totalSalesData = Transaction::where('vendor_id', $vendor->id) // Query transactions for all time
                ->where('status', 'success')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // Return all data in JSON format
            return response()->json([
                'available_vendor_earnings' => $availableEarnings,
                'todays_vendor_sales' => [
                    'total_amount' => $todaySalesData->total_amount ?? 0,
                    'sale_count' => $todaySalesData->sale_count ?? 0
                ],
                'total_vendor_sales' => [
                    'total_amount' => $totalSalesData->total_amount ?? 0,
                    'sale_count' => $totalSalesData->sale_count ?? 0
                ],
                'total_withdrawals' => $totalWithdrawals,
            ], 200);
        } catch (\Exception $e) {
            // Error handling
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function affiliateDashboardMetrics(Request $request)
    {
        try {
            // Get authenticated affiliate
            $affiliate = Auth::guard('sanctum')->user();

            if (!$affiliate) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Optional date filters for metrics
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

            // 4. Total Withdrawals (Sum all withdrawals for the user)
            $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available Affiliate Earnings (Total earnings for the affiliate)
            $availableEarn = Transaction::where('affiliate_id', $affiliate->aff_id)
                ->whereNotNull('product_id')
                ->where('status', 'success') // Transaction must be successful
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('org_aff');  // Sum of earnings amount (affiliate share)

            // Calculate available earnings
            $availableEarnings = $availableEarn - $totalWithdrawals;

            // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
            $todaySalesData = Transaction::where('affiliate_id', $affiliate->aff_id)
                ->whereNull('product_id')
                ->where('status', 'success') // Query Sales model
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
            $totalSalesData = Transaction::where('affiliate_id', $affiliate->aff_id) // Fixed to use aff_id
                ->whereNotNull('product_id')
                ->where('status', 'success')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // Return all data in JSON format
            return response()->json([
                'available_affiliate_earnings' => $availableEarnings,
                'todays_affiliate_sales' => [
                    'total_amount' => $todaySalesData->total_amount ?? 0,
                    'sale_count' => $todaySalesData->sale_count ?? 0
                ],
                'total_affiliate_sales' => [
                    'total_amount' => $totalSalesData->total_amount ?? 0,
                    'sale_count' => $totalSalesData->sale_count ?? 0
                ],
                'total_withdrawals' => $totalWithdrawals,
            ], 200);
        } catch (\Exception $e) {
            // Error handling
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function promoteProducts(Request $request)
    {
        $user = auth()->user();

        // Set default pagination size or get it from the request
        $perPage = $request->get('per_page', 20); // Default is 20 products per page

        // Check if user has market access, paid onboard, and does not have a referral ID
        if ($user->market_access &&  is_null($user->refferal_id)) {
            // User can see all products
            $products = Product::query();
        } else {
            // Find all successful transactions for the user associated with vendors
            $transactions = Transaction::where('email', $user->email)
                ->where('status', 'success')
                ->whereNotNull('vendor_id')
                ->whereNotNull('product_id')
                ->get();

            if ($transactions->isEmpty()) {
                return response()->json(['message' => 'No products available for you.', 'success' => false], 403);
            }

            // Extract vendor IDs from the transactions
            $vendorIds = $transactions->pluck('vendor_id')->unique();

            // Filter products by all vendors the user has purchased from
            $products = Product::whereIn('vendor_id', $vendorIds);
        }

        // Apply additional filters for commission and name if provided
        // Apply commission range filter if provided
        if ($request->has('min_commission') && $request->has('max_commission')) {
            $products->whereBetween('commission', [(float)$request->min_commission, (float)$request->max_commission]);
        }

        if ($request->has('name')) {
            $products->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // Fetch the products with pagination
        $paginatedProducts = $products->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedProducts->items(), // The products data
            'pagination' => [
                'current_page' => $paginatedProducts->currentPage(),
                'last_page' => $paginatedProducts->lastPage(),
                'total' => $paginatedProducts->total(),
                'per_page' => $paginatedProducts->perPage(),
            ]
        ]);
    }

    public function viewAffiliateProducts($id)
    {
        $user = auth()->user();  // Get the authenticated user

        // Fetch the product by ID
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'success' => false], 404);
        }

        // Check if the user has market access, paid onboard, and does not have a referral ID
        if ($user->market_access && is_null($user->refferal_id)) {
            // User can see all products, no further conditions needed
            return response()->json(['success' => true, 'data' => $product], 200);
        }

        // Check if the user has purchased from this vendor before, regardless of the specific product
        $hasPurchasedFromVendor = Transaction::where('user_id', $user->id)
            ->where('vendor_id', $product->vendor_id)
            ->where('status', 'success')  // Use 'success' to ensure only successful transactions count
            ->exists();

        if ($hasPurchasedFromVendor) {
            // User has previously purchased from this vendor, allow access to the product
            return response()->json(['success' => true, 'data' => $product], 200);
        }

        // If the user hasn't purchased from this vendor, deny access
        return response()->json(['message' => 'You do not have access to view this product.', 'success' => false], 403);
    }

    public function salesAffiliate()
    {
        $user = Auth::user();
        $totalNoSales = Transaction::whereNotNull('affiliate_id')->where('vendor_id', $user->id)->where('status', 'success')->count();


        return response()->json([
            'message' => "affilaite number of sales",
            'success' => true,
            'no of sales' => $totalNoSales
        ]);
    }

}
