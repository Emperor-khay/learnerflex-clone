<?php

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RandomController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Auths\LoginController;
use App\Http\Controllers\Auths\LogoutController;
use App\Http\Controllers\SecondVendorController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Auths\RegisterController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\Flutterwave\PaymentController;
use App\Http\Controllers\PasswordReset\NewPasswordReset;
use App\Http\Controllers\Payment\PayStackEbookController;
use App\Http\Controllers\Flutterwave\WithdrawalController;
use App\Http\Controllers\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use App\Http\Controllers\Payment\MarketplacePaymentController;
use App\Http\Controllers\PasswordReset\PasswordResetController;
use App\Http\Controllers\SuperAdmin\SuperAdminVendorController;
use App\Http\Controllers\SuperAdmin\SuperAdminProductController;
use App\Http\Controllers\SuperAdmin\SuperAdminAffiliateController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminTransactionController;

//Public Routes

Route::post('/payment/make-payment', [PaystackController::class, 'make_payment']);
// Route for handling the payment callback
Route::post('/payment/callback', [PaystackController::class, 'payment_callback'])->name('payment.callback');
// Route::post('/ebook-mentorship/make-payment', [PayStackEbookController::class, 'make_payment']);
// // Route for handling the payment callback
// Route::post('/ebook-mentorship/callback', [PayStackEbookController::class, 'paymentCallback']);
//password resetting routes
Route::post('/password/reset-link', [PasswordResetController::class, 'sendPasswordResetLink']);
Route::post('/password/new-password', [NewPasswordReset::class, 'resetPassword']);
Route::get('/user/{id}', [UserController::class, 'getUserById']);
Route::get('/user/store-details/{id}', [VendorController::class, 'getVendorData']);
Route::get('/vendor-details/{id}', [VendorController::class, 'getVendorStore']);
// Route::get('/vendor-store/{id}', [VendorController::class, 'getVendorStore']);
Route::get('/download', [RandomController::class, 'downloadFile'])->name('product.download');


Route::post('/request-access-token', [RandomController::class, 'requestAccessToken']);
Route::post('/validate-access-token', [RandomController::class, 'validateAccessToken']);


// unlock market
//one time payment
Route::post('/marketplace/payment', [MarketplacePaymentController::class, 'payment'])->name('marketplace.payment');
Route::post('/marketplace/payment/callback', [MarketplacePaymentController::class, 'payment_callback'])->name('marketplace.payment.callback');

// Route::post('/oh', [MarketplacePaymentController::class, 'redirectToGateway'])->name('oh');
// Route::get('/ohyes', [MarketplacePaymentController::class, 'handleGatewayCallback'])->name('ohyes');

//User Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'initiateRegistration']);
    Route::match(['get', 'post'], '/payment/callback', [RegisterController::class, 'handlePaymentCallback'])->name('auth.payment.callback');
    Route::post('/login', [LoginController::class, 'attemptUser']);
});
//Admin Authentication
Route::post('/admin/login', [SuperAdminAuthController::class, 'login']);

// Affiliate routes group
Route::middleware(['auth:sanctum', 'role:affiliate'])->prefix('affiliate')->group(function () {

    Route::get('/dashboard', [AffiliateController::class, 'affiliateDashboardMetrics']);

    // marketplace
    // Route::get('/{id}/product/{reffer_id}', [ProductController::class, 'getProduct']);

    //profile routes
    Route::post('/update/profile', [SecondVendorController::class, 'handleUserProfile']);

    //vendor requesting to be a vendor
    Route::post('/request-vendor', [AffiliateController::class, 'sendVendorRequest']);
    Route::post('/check-sale', [AffiliateController::class, 'checkSaleByEmail']);


    // withdrawals
    Route::post('/request/withdrawal', [WithdrawalController::class, 'index']);
    Route::get('/withdrawals', [WithdrawalController::class, 'WithdrawRecord']);
    //transactions
    Route::get('/transactions', [AffiliateController::class, 'transactions']);
    //products routes
    Route::get('/products', [AffiliateController::class, 'affiliateproducts']);

    Route::get('/products/{id}', [AffiliateController::class, 'showAffiliateProduct']);

    Route::post('/unlock/market', [AffiliateController::class, 'unlockMarketAccess']);
    Route::post('/unlock/market/callback', [AffiliateController::class, 'marketAccessCallback'])->name('unlock.market.callback');

    Route::post('/change-password', [SecondVendorController::class, 'changePassword'])->name('change-password');

    Route::post('/logout', [LogoutController::class, 'logout']);

});

// Vendor routes group
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', [SecondVendorController::class, 'vendorDashboardMetrics']);
    Route::get('/affiliate/dashboard', [AffiliateController::class, 'affiliateDashboardMetrics']);

    //profile routes
    Route::post('/update/profile', [SecondVendorController::class, 'handleUserProfile']);
    Route::post('/update/store/profile', [SecondVendorController::class, 'createOrUpdateVendor']);


    Route::get('/data', [VendorController::class, 'getAuthenticatedVendorData']);
    Route::get('/affiliate/products', [AffiliateController::class, 'affiliateproducts']);
    Route::get('/affiliate/products/{id}', [AffiliateController::class, 'showAffiliateProduct']);
    //gets sales data
    Route::get('/affiliate/sales', [SecondVendorController::class, 'salesAffiliate']);

    //view all products from a particular vendor
    Route::get('/products', [VendorController::class, 'viewProductsByVendor']);
    Route::get('/product/{id}', [VendorController::class, 'viewProductById']);
    Route::delete('/product/delete/{id}', [VendorController::class, 'deleteProduct']);
    Route::post('/product/digital/create', [SuperAdminProductController::class, 'store']);
    Route::post('/product/other/create', [VendorController::class, 'createOtherProduct']);
    Route::post('/products/digital/{id}/update', [VendorController::class, 'editDigitalProduct']);
    Route::post('/products/other/{id}/update', [VendorController::class, 'editOtherProduct']);
    Route::delete('/products/{id}/delete', [VendorController::class, 'destroy']);

    Route::get('/transactions', [VendorController::class, 'getVendorSales']);
    Route::post('/check-sale', [AffiliateController::class, 'checkSaleByEmail']);

    Route::get('/product-performance', [VendorController::class, 'productPerformance']);
    Route::get('/affiliate-performance/{affiliateId}', [VendorController::class, 'viewAffiliatePerformance']);
    Route::get('/affiliate-details/{aff_id}', [VendorController::class, 'getAffDetails']);

    Route::post('/unlock/market', [AffiliateController::class, 'unlockMarketAccess']);
    Route::post('/unlock/market/callback', [AffiliateController::class, 'marketAccessCallback'])->name('unlock.market.callback');

    //add new product
    Route::post('/product/add-product', [ProductController::class, 'addProduct']);

    // withdrawals
    Route::post('/request/withdrawal', [WithdrawalController::class, 'index']);
    Route::get('/withdrawals', [WithdrawalController::class, 'WithdrawRecord']);
    Route::post('/change-password', [SecondVendorController::class, 'changePassword'])->name('change-password');


    Route::post('/logout', [LogoutController::class, 'logout']);
});


// Admin routes group
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Super Admin Dashboard Routes
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'getDashboardData']);
    Route::get('/analytics', [SuperAdminDashboardController::class, 'analytics']);


    Route::prefix('products')->group(function () {
        Route::get('/', [SuperAdminProductController::class, 'index']);
        Route::get('{id}', [SuperAdminProductController::class, 'show']);
        Route::post('/digital/create', [SuperAdminProductController::class, 'store']);
        Route::post('/other/create', [VendorController::class, 'createOtherProduct']);
        Route::post('/digital/{id}/update', [VendorController::class, 'editDigitalProduct']);
        Route::post('/other/{id}/update', [VendorController::class, 'editOtherProduct']);
        Route::delete('/{id}/delete', [VendorController::class, 'destroy']);
    });
    Route::prefix('users')->group(function () {
        Route::get('/', [SuperAdminUserController::class, 'index']); // View  Users
        Route::get('/{id}', [SuperAdminUserController::class, 'showUser']); // View by role and id
        Route::post('/create', [SuperAdminUserController::class, 'createUser']); // Create role-based entity
        Route::post('/edit/{id}', [SuperAdminUserController::class, 'updateUser']); // Update by role and id
        Route::post('/delete/{id}', [SuperAdminUserController::class, 'deleteUser']); // Delete by role and id
        Route::get('/vendor-status', [SuperAdminUserController::class, 'filterVendorStatus']);
    });

    // Transactions Route
    Route::get('/transactions', [SuperAdminTransactionController::class, 'index']);

    Route::post('/affiliate/bulk-upload', [SuperAdminAffiliateController::class, 'bulkUpload']); // Bulk upload affiliates
    //set affiliates to vendor
    Route::get('/vendor-requests', [SuperAdminUserController::class, 'requestToBeVendor']);
    Route::post('/accept-vendor-request/{id}', [SuperAdminUserController::class, 'upgradeAffiliateToVendor']);
    Route::get('/withdrawals/pending/download', [SuperAdminTransactionController::class, 'downloadPendingWithdrawals']);
    Route::post('/withdrawals/approve-all', [SuperAdminTransactionController::class, 'approveAllPendingWithdrawals']);
    // Route::get('/withdrawals', [WithdrawalController::class, 'getFilteredWithdrawals']);


    Route::post('/change-password', [SecondVendorController::class, 'changePassword'])->name('change-password');

    Route::post('/logout', [SuperAdminAuthController::class, 'logout']);
});

//test endpoints
Route::get('/test', function () {
    try {
        // Revenue and Count from Marketplace Unlocks
        $marketplaceUnlocks = DB::table('transactions')
            ->whereNull('product_id')
            ->whereNull('vendor_id')
            ->where('description', 'marketplace_unlock')
            ->where('status', 'success');

        $marketplaceRevenue = $marketplaceUnlocks->sum('amount');
        $marketplaceCount = $marketplaceUnlocks->count();

        // Revenue and Count from Signups
        $signups = DB::table('transactions')
            ->whereNull('product_id')
            ->whereNull('vendor_id')
            ->where('description', 'signup_fee')
            ->where('status', 'success');

        $signupRevenue = $signups->sum('amount');
        $signupCount = $signups->count();

        // Total Revenue Generated (Product Sales + Signups + Marketplace Unlocks)
        $productSales = DB::table('transactions')
            ->whereNotNull('product_id')
            ->whereNotNull('vendor_id')
            ->where('status', 'success');

        $productSalesRevenue = $productSales->sum('amount');
        $productSalesCount = $productSales->count();

        $totalRevenue = $productSalesRevenue + $signupRevenue + $marketplaceRevenue;

        // Today's Earnings
        $orgEarningsToday = DB::table('sales')
            ->whereDate('created_at', today())
            ->sum('org_company'); // Organization's share
        $orgEarningsTodayCount = DB::table('sales')
            ->whereDate('created_at', today())
            ->count();

        $affiliateEarningsToday = DB::table('sales')
            ->whereDate('created_at', today())
            ->sum('org_aff'); // Affiliates' share
        $affiliateEarningsTodayCount = DB::table('sales')
            ->whereDate('created_at', today())
            ->whereNotNull('affiliate_id') // Count only those involving affiliates
            ->count();

        $vendorEarningsToday = DB::table('sales')
            ->whereDate('created_at', today())
            ->sum('org_vendor'); // Vendors' share
        $vendorEarningsTodayCount = DB::table('sales')
            ->whereDate('created_at', today())
            ->count();

        // Unpaid Balances
        $unpaidAffiliateBalance = DB::table('sales')->sum('org_aff') // Total owed
            - DB::table('withdrawals')
            ->join('users', 'withdrawals.user_id', '=', 'users.id')
            ->where('users.role', 'affiliate')
            ->where('withdrawals.status', 'success') // Only successful withdrawals
            ->sum('withdrawals.amount'); // Total withdrawn

        $unpaidVendorBalance = DB::table('sales')->sum('org_vendor') // Total owed
            - DB::table('withdrawals')
            ->join('users', 'withdrawals.user_id', '=', 'users.id')
            ->where('users.role', 'vendor')
            ->where('withdrawals.status', 'success') // Only successful withdrawals
            ->sum('withdrawals.amount'); // Total withdrawn

        // Total Payouts
        $affiliatePayouts = DB::table('withdrawals')
            ->join('users', 'withdrawals.user_id', '=', 'users.id')
            ->where('users.role', 'affiliate')
            ->where('withdrawals.status', 'success') // Only successful withdrawals
            ->sum('withdrawals.amount'); // Total affiliate payouts

        $vendorPayouts = DB::table('withdrawals')
            ->join('users', 'withdrawals.user_id', '=', 'users.id')
            ->where('users.role', 'vendor')
            ->where('withdrawals.status', 'success') // Only successful withdrawals
            ->sum('withdrawals.amount'); // Total vendor payouts

        // Return data
        return response()->json([
            'total_revenue' => $totalRevenue,
            'product_sales_revenue' => $productSalesRevenue,
            'product_sales_count' => $productSalesCount,
            'marketplace_revenue' => $marketplaceRevenue,
            'marketplace_count' => $marketplaceCount,
            'signup_revenue' => $signupRevenue,
            'signup_count' => $signupCount,
            'org_earnings_today' => [
                'amount' => $orgEarningsToday,
                'count' => $orgEarningsTodayCount,
            ],
            'affiliate_earnings_today' => [
                'amount' => $affiliateEarningsToday,
                'count' => $affiliateEarningsTodayCount,
            ],
            'vendor_earnings_today' => [
                'amount' => $vendorEarningsToday,
                'count' => $vendorEarningsTodayCount,
            ],
            'unpaid_affiliate_balance' => $unpaidAffiliateBalance,
            'unpaid_vendor_balance' => $unpaidVendorBalance,
            'total_affiliate_payouts' => $affiliatePayouts,
            'total_vendor_payouts' => $vendorPayouts,
        ]);
    } catch (\Exception $e) {
        // Handle any errors
        return response()->json([
            'error' => 'Failed to retrieve analytics data',
            'message' => $e->getMessage(),
        ], 500);
    }
});
