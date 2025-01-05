<?php

namespace App\Http\Controllers;

use App\Enums\TransactionDescription;
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
use Illuminate\Support\Facades\Validator;
use Unicodeveloper\Paystack\Facades\Paystack;
use Illuminate\Validation\ValidationException;

class AffiliateController extends Controller
{
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
    //             ->where('type', 'affiliate')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('amount');

    //         // 1. Available Affiliate Earnings (Total earnings for the affiliate)
    //         $availableEarn = Sale::where('affiliate_id', $affiliate->aff_id)
    //             ->where('status', 'success') // Transaction must be successful
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->sum('org_aff');  // Sum of earnings amount (affiliate share)

    //         // Calculate available earnings
    //         $availableEarnings = $availableEarn - $totalWithdrawals;

    //         // 2. Today's Affiliate Sales (Sales with affiliate for the current day - both count and amount)
    //         $todaySalesData = Sale::where('affiliate_id', $affiliate->aff_id)
    //             ->where('status', 'success') // Query Sales model
    //             ->whereDate('created_at', Carbon::today())  // Today's sales
    //             ->selectRaw('COUNT(*) as sale_count, SUM(org_aff) as total_amount')
    //             ->first();

    //         // 3. Total Affiliate Sales (All-time or filtered by date sales with affiliate - both count and amount)
    //         $totalSalesData = Sale::where('affiliate_id', $affiliate->aff_id) // Fixed to use aff_id
    //             ->where('status', 'success')
    //             ->when($startDate, function ($query) use ($startDate, $endDate) {
    //                 $query->whereBetween('created_at', [$startDate, $endDate]);
    //             })
    //             ->selectRaw('COUNT(*) as sale_count, SUM(org_aff) as total_amount')
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

            // Total Withdrawals
            $totalWithdrawals = Withdrawal::where('user_id', $affiliate->id)
                ->where('status', 'approved')
                ->where('type', 'affiliate')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('amount');

            // Available Affiliate Earnings
            $availableEarn = Sale::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->sum('org_aff');

            $availableEarnings = $availableEarn - $totalWithdrawals;

            // Today's Affiliate Sales
            $todaySalesData = Sale::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success')
                ->whereDate('created_at', Carbon::today())
                ->selectRaw('COUNT(*) as sale_count, SUM(org_aff) as total_amount')
                ->first();

            // Total Affiliate Sales
            $totalSalesData = Sale::where('affiliate_id', $affiliate->aff_id)
                ->where('status', 'success')
                ->when($startDate, function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->selectRaw('COUNT(*) as sale_count, SUM(org_aff) as total_amount')
                ->first();

            // Convert amounts to naira
            $availableEarningsNaira = $availableEarnings / 100;
            $totalWithdrawalsNaira = $totalWithdrawals / 100;
            $todaysTotalAmountNaira = ($todaySalesData->total_amount ?? 0) / 100;
            $totalSalesAmountNaira = ($totalSalesData->total_amount ?? 0) / 100;

            // Return all data in JSON format
            return response()->json([
                'available_affiliate_earnings' => $availableEarningsNaira,
                'todays_affiliate_sales' => [
                    'total_amount' => $todaysTotalAmountNaira,
                    'sale_count' => $todaySalesData->sale_count ?? 0,
                ],
                'total_affiliate_sales' => [
                    'total_amount' => $totalSalesAmountNaira,
                    'sale_count' => $totalSalesData->sale_count ?? 0,
                ],
                'total_withdrawals' => $totalWithdrawalsNaira,
            ], 200);
        } catch (\Exception $e) {
            // Error handling
            return response()->json(['error' => 'An error occurred'], 500);
            Log::error('Aff dashboard Error: ' . $e->getMessage());
        }
    }




    public function unlockMarketAccess(Request $request)
    {
        $user = auth()->user();
        // Check if the user is eligible for market access (has not paid and has a referral ID)
        if ($user->market_access) {
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
            'callback_url' => 'https://learnerflex.com/dashboard/u/marketplace 
' . '?email=' . urlencode($user->email) . '&order_id=' . urlencode($orderID),
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
                'affiliate_id' => null,
                'product_id' => null,
                'amount' => $formData['amount'],
                'currency' => $formData['currency'],
                'status' => 'pending',
                'description' => TransactionDescription::MARKETPLACE_UNLOCK->value,
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
            // Validate the POST request payload
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'order_id' => 'required',
                'reference' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Extract the validated inputs
            $email = $request->input('email');
            $orderID = $request->input('order_id');
            $reference = $request->input('reference');
            // Verify payment with Paystack
            Log::info('Verifying payment with Paystack', ['reference' => $reference]);
            $response = json_decode($this->verify_payment($reference));

            if (!$response || $response->data->status !== "success") {
                Log::error('Payment verification failed', [
                    'reference' => $reference,
                    'response' => $response
                ]);
                return response()->json(['message' => 'Transaction not successful', 'success' => false]);
            }

            // Verify the transaction status
            if ($response->data->status === "success") {
                // Get the authenticated user
                $user = auth()->user();

                // Update the user's market access and clear referral ID
                $user->update([
                    'market_access' => true,
                    'refferal_id' => null,
                ]);

                // Locate the transaction based on email and order ID
                $transaction = Transaction::where('email', $email)
                    ->where('transaction_id', $orderID)
                    ->latest()
                    ->first();

                // Update the transaction details if found
                if ($transaction) {
                    $transaction->update([
                        'tx_ref' => $reference,
                        'status' => $response->data->status
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Market access unlocked successfully!',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment failed or not verified.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verify_payment($reference)
    {
        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . env("PAYSTACK_SECRET_KEY"),
            "Cache-Control: no-cache"
        ));

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }


    // public function affiliateproducts(Request $request)
    // {
    //     $user = auth()->user();

    //     // Set default pagination size or get it from the request
    //     $perPage = $request->get('per_page', 20);

    //     // Determine product query based on user's access level
    //     if ($user->market_access) {
    //         // User can see all products
    //         $products = Product::with([
    //             'user:id,name,email,phone,country,image',  // User details as store owner
    //             'vendor:id,user_id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link' // Vendor business details
    //         ]);
    //     } else {

    //         $transactions = Transaction::where('email', $user->email)
    //             ->where('status', 'success')
    //             ->whereNotNull('vendor_id')
    //             ->whereNotNull('product_id')
    //             ->pluck('vendor_id')
    //             ->unique();

    //         if ($transactions->isEmpty()) {
    //             return response()->json(['message' => 'No products available for you.', 'success' => false], 403);
    //         }

    //         // Extract vendor IDs
    //         $vendorIds = $transactions;

    //         // Filter products by vendors the user has purchased from
    //         $products = Product::with([
    //             'user:id,name,email,phone,country,image',  // User details
    //             'vendor:id,user_id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link' // Vendor details
    //         ])->whereIn('user_id', $vendorIds);
    //     }

    //     // Apply additional filters for commission and name if provided
    //     if ($request->has('min_commission') && $request->has('max_commission')) {
    //         $products->whereBetween('commission', [(float)$request->min_commission, (float)$request->max_commission]);
    //     }

    //     if ($request->has('name')) {
    //         $products->where('name', 'LIKE', '%' . $request->name . '%');
    //     }

    //     // Fetch paginated products
    //     $paginatedProducts = $products->paginate($perPage);

    //     // Remove `access_link` from each product in the response
    //     $filteredProducts = $paginatedProducts->through(function ($product) {
    //         unset($product['access_link']);
    //         return $product;
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data' => $filteredProducts,
    //         'pagination' => [
    //             'current_page' => $paginatedProducts->currentPage(),
    //             'last_page' => $paginatedProducts->lastPage(),
    //             'total' => $paginatedProducts->total(),
    //             'per_page' => $paginatedProducts->perPage(),
    //         ]
    //     ]);
    // }

    public function affiliateproducts(Request $request)
    {
        $user = auth()->user();

        // Set default pagination size or get it from the request
        $perPage = $request->get('per_page', 20);

        // Determine product query based on user's access level
        if ($user->market_access) {
            // User can see all products
            $products = Product::with([
                'user:id,name,email,phone,country,image',  // User details as store owner
                'vendor:id,user_id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link' // Vendor business details
            ]);
        } else {
            // Check transactions with successful status
            $transactions = Transaction::where('email', $user->email)
                ->where('status', 'success')
                ->whereNotNull('vendor_id')
                ->whereNotNull('product_id')
                ->pluck('vendor_id')
                ->unique();

            if ($transactions->isNotEmpty()) {
                // Extract vendor IDs
                $vendorIds = $transactions;

                // Filter products by vendors the user has purchased from
                $products = Product::with([
                    'user:id,name,email,phone,country,image',  // User details
                    'vendor:id,user_id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link' // Vendor details
                ])->whereIn('user_id', $vendorIds);
            } else {
                // Check if the user has a transaction with is_onboarded set to true
                $onboardedVendors = Transaction::where('email', $user->email)
                    ->where('is_onboarded', true)
                    ->whereNotNull('vendor_id')
                    ->pluck('vendor_id')
                    ->unique();

                if ($onboardedVendors->isNotEmpty()) {
                    // Filter products by vendors the user is onboarded to
                    $products = Product::with([
                        'user:id,name,email,phone,country,image',  // User details
                        'vendor:id,user_id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link' // Vendor details
                    ])->whereIn('user_id', $onboardedVendors);
                } else {
                    return response()->json(['message' => 'No products available for you.', 'success' => false], 403);
                }
            }
        }

        // Apply additional filters for commission and name if provided
        if ($request->has('min_commission') && $request->has('max_commission')) {
            $products->whereBetween('commission', [(float)$request->min_commission, (float)$request->max_commission]);
        }

        if ($request->has('name')) {
            $products->where('name', 'LIKE', '%' . $request->name . '%');
        }

        // Fetch paginated products
        $paginatedProducts = $products->paginate($perPage);

        // Remove `access_link` from each product in the response
        $filteredProducts = $paginatedProducts->through(function ($product) {
            unset($product['access_link']);
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $filteredProducts,
            'pagination' => [
                'current_page' => $paginatedProducts->currentPage(),
                'last_page' => $paginatedProducts->lastPage(),
                'total' => $paginatedProducts->total(),
                'per_page' => $paginatedProducts->perPage(),
            ]
        ]);
    }




    public function showAffiliateProduct($id)
    {
        $user = auth()->user();  // Get the authenticated user

        // Fetch the product by ID
        $product = Product::with([
            'vendor:id,name,photo,description,x_link,ig_link,yt_link,fb_link,tt_link',  // Vendor details
            'user:id,name,email,phone,country,image' // User details
        ])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'success' => false], 404);
        }

        // Hide the access_link field
        $product->makeHidden(['access_link']);

        // Check if the user has market access, paid onboard, and does not have a referral ID
        if ($user->market_access && is_null($user->refferal_id)) {
            // User can see all products, no further conditions needed
            return response()->json(['success' => true, 'data' => $product], 200);
        }

        // Check if the user has purchased from this vendor before, regardless of the specific product
        $hasPurchasedFromVendor = Transaction::where('email', $user->email)
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

        $user = auth()->user();
        $saleUrl = $validate['sale_url'];
        $description = $validate['description'] ?? null;

        try {
            // Check for existing vendor_status record
            $existingVendorStatus = DB::table('vendor_status')
                ->where('user_id', $user->id)
                ->first();

            if ($existingVendorStatus) {
                // Prevent editing if the status is "pending"
                if ($existingVendorStatus->status === 'pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot edit your request while it is still pending approval.',
                    ], 403);
                }

                // Allow editing if the status is "rejected"
                if ($existingVendorStatus->status === 'rejected') {
                    DB::table('vendor_status')
                        ->where('user_id', $user->id)
                        ->update([
                            'sale_url' => $saleUrl,
                            'description' => $description,
                            'status' => 'pending',
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // Create a new record if none exists
                DB::table('vendor_status')->insert([
                    'user_id' => $user->id,
                    'sale_url' => $saleUrl,
                    'description' => $description,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Send notification email
            try {
                Mail::to('learnerflexltd@gmail.com')->send(new VendorAccountWanted($user, $saleUrl));
            } catch (\Exception $e) {
                Log::error('Error sending mail', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vendor Request sent successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing your request.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
