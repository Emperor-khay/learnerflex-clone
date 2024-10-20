<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Earning;
use App\Models\Product;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Mail\VendorAccountWanted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\AffiliateVendorRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Unicodeveloper\Paystack\Facades\Paystack;
use Illuminate\Validation\ValidationException;

class AffiliateController extends Controller
{
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

            // 4. Total Withdrawals (Sum all withdrawals for the affiliate)
            $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
                ->where('status', 'approved')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // 1. Available Affiliate Earnings (Total earnings for the affiliate)
            $availableEarn = Transaction::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success') // Transaction must be successful
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('org_aff');  // Sum of earnings amount (affiliate share)

            // Calculate available earnings
            $availableEarnings = $availableEarn - $totalWithdrawals;

            // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
            $todaySalesData = Transaction::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success') // Query Sales model
                ->whereDate('created_at', Carbon::today())  // Today's sales
                ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
                ->first();

            // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
            $totalSalesData = Transaction::where('affiliate_id', $affiliate->aff_id) // Fixed to use aff_id
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


    // public function affiliateDashboardMetrics(Request $request)
    // {
    //     try {
    //         // Get authenticated affiliate
    //         $affiliate = Auth::guard('sanctum')->user();

    //         if (!$affiliate) {
    //             return response()->json(['error' => 'Unauthorized'], 403);
    //         }

    //         // Optional date filters for metrics
    //         $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
    //         $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now();

    //         // 4. Total Withdrawals (Sum all withdrawals for the affiliate)
    //         $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
    //             ->where('status', 'approved')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // 1. Available Affiliate Earnings (Total earnings for the affiliate)
    //         $availableEarn = Transaction::where('affiliate_id', $affiliate->aff_id)
    //             ->where('status', 'success') //  Transaction
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('org_aff');  // Sum of earnings amount

    //         // Calculate available earnings
    //         $availableEarnings = $totalWithdrawals - $availableEarn;

    //         // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
    //         $todaySalesData = Transaction::where('affiliate_id', $affiliate->aff_id)
    //             ->where('status', 'success') // Query Sales model
    //             ->whereDate('created_at', Carbon::today())  // Today's sales
    //             ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
    //             ->first();

    //         // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
    //         $totalSalesData = Transaction::where('affiliate_id', $affiliate->id)
    //         ->where('status', 'success')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->selectRaw('COUNT(*) as sale_count, SUM(amount) as total_amount')
    //             ->first();

    //         // Return all data in JSON format
    //         return response()->json([
    //             'available_affiliate_earnings' => $availableEarnings,
    //             'todays_affiliate_sales' => [
    //                 'total_amount' => $todaySalesData->total_amount ?? 0,
    //                 'sale_count' => $todaySalesData->sale_count ?? 0
    //             ],
    //             'total_affiliate_sales' => [
    //                 'total_amount' => $totalSalesData->total_amount ?? 0,
    //                 'sale_count' => $totalSalesData->sale_count ?? 0
    //             ],
    //             'total_withdrawals' => $totalWithdrawals,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // Error handling
    //         return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

    public function unlockMarketAccess(Request $request)
    {
        $user = auth()->user();
        // Check if the user is eligible for market access (has not paid and has a referral ID)
        if ($user->market_access && $user->has_paid_onboard && is_null($user->refferal_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You already have market access or have already paid for onboarding.',
            ], 403);
        }

        // Generate a unique transaction reference and order ID for each market access payment
        $orderID = strtoupper(uniqid() . $user->id); // Random 10-character string for order ID

        // Prepare the data for Paystack payment
        $formData = [
            'email' => $user->email,  // Authenticated user's email
            'amount' => 1100 * 100, // Amount in kobo (NGN)
            'currency' => 'NGN',
            'callback_url' => route('unlock.market.callback') . '?email=' . urlencode($user->email) . '&order_id=' . urlencode($orderID),
            'metadata' => json_encode([
                'description' => 'Unlock Market Access - Full access to promote products',
                'orderID' => $orderID,
            ]),
        ];

        try {
            // Initialize the payment using Paystack via the Unicodeveloper package
            $paymentData = Paystack::getAuthorizationUrl($formData);

            // Store the transaction in the database
            Transaction::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'affiliate_id' => 0,
                'product_id' => 0,
                'amount' => $formData['amount'],
                'currency' => $formData['currency'],
                'status' => 'pending',
                'org_company' => 0,
                'org_vendor' => 0,
                'org_aff' => 0,
                'tx_ref' => null,
                'transaction_id' => $orderID, // Save the generated order ID
                'meta' => json_encode([
                    'description' => 'Unlock Market Access - Full access to promote products',
                    'orderID' => $orderID,
                ]),
            ]);

            // Return the authorization URL in the JSON response
            return response()->json([
                'success' => true,
                'authorization_url' => $paymentData, // Authorization URL returned from Paystack
            ], 200);
        } catch (\Exception $e) {
            Log::error('Payment Initialization Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function marketAccessCallback(Request $request)
    {

        try {
            $email = urldecode($request->get('email'));
            $orderID = urldecode($request->get('order_id'));
            $reference = request('reference');  // Get reference from the callback
            $paymentDetails = Paystack::getPaymentData();
            // Verify transaction using Paystack reference

            if ($paymentDetails['data']['status'] == "success") {
                // Get the authenticated user
                $user = auth()->user();
                // Update user to grant market access
                $user->update([
                    'market_access' => true,
                    'refferal_id' => null,

                ]);
                // Update the transaction record
                $transaction = Transaction::where('email', $email)->where('transaction_id', $orderID)->latest()->first();

                if ($transaction) {
                    $transaction->update([
                        'tx_ref' => request('reference'),
                        'status' => $paymentDetails['data']['status'],
                        'is_onboard' => 1,
                        'tx_ref' => $reference,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Market access unlocked successfully!'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment failed or not verified.'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function affiliateproducts(Request $request)
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

    public function showAffiliateProducts($id)
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


    public function sendVendorRequest(Request $request)
    {
        $validate = $request->validate([
            'sale_url' => 'required|string',
            'description' => 'nullable|string',
        ]);
        $user = Auth::user();
        $saleurl = $validate['sale_url'];

        DB::table('vendor_status')->insert([
            'user_id' => $user->id,
            'sale_url' => $validate['sale_url'],
            'description' => $validate['description'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        Mail::to('learnerflexltd@gmail.com')->send(new VendorAccountWanted($user, $saleurl));
        // Send email to the affiliate
        Mail::to($user->email)->send(new AffiliateVendorRequest($user, $saleurl));

        return response()->json(['success' => true, 'message' => 'Vendor Request sent successfully'], 201);
    }

    public function checkSaleByEmail(Request $request)
    {
        //Validate the email field directly from the request
        $request->validate([
            'email' => ['required', 'email']
        ]);

        // Get the authenticated user
        $authUser = Auth::user();
        $email = $request->input('email');

        // Fetch transactions where the affiliate is responsible for the sale by matching aff_id and email
        $transactions = Transaction::where('affiliate_id', $authUser->aff_id)
            ->where('email', $email)
            ->whereNotNull('product_id')
            ->where('status', 'success')
            ->get();

        // Check if any transactions exist for the given email
        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No sales found for the provided email by you.',
            ], 404);
        }

        // Return the transaction data if found
        return response()->json([
            'success' => true,
            'message' => 'Transactions found for this affiliate and email.',
            'data' => $transactions
        ], 200);
    }

    public function transactions(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Optional: Retrieve the status filter from request
        $status = $request->input('status');

        // Query the transactions where email or user_id matches
        $transactionsQuery = Transaction::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('email', $user->email);
        });

        // If a status is provided, filter by it
        if ($status) {
            $transactionsQuery->where('status', $status);
        }

        // Execute the transaction query
        $transactions = $transactionsQuery->get();

        // Query the withdrawals where user_id matches
        $withdrawalsQuery = Withdrawal::where('user_id', $user->id);

        // If a status is provided, filter by it
        if ($status) {
            $withdrawalsQuery->where('status', $status);
        }

        // Execute the withdrawal query
        $withdrawals = $withdrawalsQuery->get();

        // Return the combined results in a JSON response
        return response()->json([
            'transactions' => $transactions,
            'withdrawals' => $withdrawals,
        ], 200);
    }
}
