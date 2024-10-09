<?php

use App\Http\Controllers\Auths\LoginController;
use App\Http\Controllers\Auths\LogoutController;
use App\Http\Controllers\Auths\RegisterController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Vendor\VendorController;
use App\Http\Controllers\Flutterwave\WithdrawalController;
use App\Http\Controllers\Flutterwave\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PasswordReset\PasswordResetController;
use App\Http\Controllers\PasswordReset\NewPasswordReset;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\Payment\MarketplacePaymentController;
use App\Http\Controllers\Payment\PayStackEbookController;
use App\Http\Controllers\SuperAdmin\SuperAdminAffiliateController;
use App\Http\Controllers\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminProductController;
use App\Http\Controllers\SuperAdmin\SuperAdminTransactionController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\SuperAdminVendorController;


Route::post('/user/get-balance', [UserController::class, 'getBalance']);
Route::post('user/total-aff-sales', [UserController::class, 'totalSaleAff']);

Route::post('/request-withdrawal', [UserController::class, 'requestWithdrawal']);

//one time payment
Route::post('/payment/initialize-marketplace', [MarketplacePaymentController::class, 'make_payment']);

Route::post('/payment/callback-marketplace', [MarketplacePaymentController::class, 'payment_callback'])->name('payment.callback');

Route::get('/vendor/{id}/total-sales', [VendorController::class, 'getVendorTotalSaleAmount']);

Route::get('vendor/{id}/transactions', [VendorController::class, 'getVendorSales']);

Route::post('vendor/students', [VendorController::class, 'students']);

Route::post('/vendor/product-performance', [VendorController::class, 'productPerformance']);

Route::get('vendor/affiliate-details/{aff_id}', [VendorController::class, 'getAffDetails']);

Route::get('/vendor/{id}/balance', [VendorController::class, 'vendorEarnings']);

Route::get('/user/{id}/balance', [UserController::class, 'affiliateEarnings']);

Route::post('user/accept-vendor-request', [VendorController::class, 'store']);


Route::post('/payment/make-payment', [PaystackController::class, 'make_payment']);

// Route for handling the payment callback
Route::post('/payment/callback', [PaystackController::class, 'payment_callback'])->name('callback');

Route::post('/ebook-mentorship/make-payment', [PayStackEbookController::class, 'make_payment']);

// Route for handling the payment callback
Route::post('/ebook-mentorship/callback', [PayStackEbookController::class, 'paymentCallback'])->name('callback');

//new marketplace

Route::get('/user/{id}/product/{reffer_id}', [ProductController::class, 'getProduct']);

//add new product
Route::post('product/add-product', [ProductController::class, 'addProduct']);

//view all products from a particular vendor
Route::get('product/view-product/{vendor_id}/', [ProductController::class, 'viewProductsByVendor']);


//get product by id
Route::get('product/view-product/{vendor_id}/{product_id}', [ProductController::class, 'viewProductByVendor']);


Route::delete('/product/delete/{id}', [ProductController::class, 'deleteProduct']);


Route::post('password/reset-link', [PasswordResetController::class, 'sendPasswordResetLink']);
Route::post('password/new-password', [NewPasswordReset::class, 'resetPassword']);

Route::post('user/request-vendor', [VendorController::class, 'sendVendorRequest']);

Route::get('/affiliate/sales', [UserController::class, 'salesAffiliate']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//test endpoints
Route::get('/test', function () {
    return response()->json(['message' => 'Checked successful']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'attemptUser']);
    Route::post('/logout', [LogoutController::class, 'logout']);
});

Route::get('/users', [UserController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/{user}/transactions', [UserController::class, 'transactions']);
    Route::patch('/users/{user}/currency', [UserController::class, 'transactions']);
    Route::post('/user/update/image', [UserController::class, 'handleUserImage']);
    Route::post('/user/update/profile', [UserController::class, 'handleUserProfile']);
    Route::patch('/users/update/{user}/vendor/status', [UserController::class, 'handleUserVendorStatus']);
    Route::post('/user/request/vendor', [UserController::class, 'handleVendorRequest']);
    Route::get('/users/{user}/vendor', [VendorController::class, 'index']);
    Route::post('/vendor/create', [VendorController::class, 'store']);
    Route::delete('/vendors/{vendor}/delete', [VendorController::class, 'delete']);

    // unlock market
    Route::get('/user/unlock/market', [ProductController::class, 'unlockMarketAccess']);

    // products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/status/{status}', [ProductController::class, 'getApprovedProducts']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/product/digital/create', [ProductController::class, 'createDigitalProduct']);
    Route::post('/product/other/create', [ProductController::class, 'createOtherProduct']);
    Route::patch('/products/{product}/update', [ProductController::class, 'edit']);
    Route::delete('/products/{product}/delete', [ProductController::class, 'destroy']);

    // withdrawals
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::get('/withdrawals/amount', [WithdrawalController::class, 'userWithdrawSum']);

    // dashboard home endpoints
    Route::get('/todays-affiliate-sales', [UserController::class, 'handleTodaysAffSales']);
    Route::get('/total-affiliate-sales', [UserController::class, 'handleTotalAffiliateSales']);
    Route::get('/available-affiliate-earnings', function () {
        return response()->json(['amount' => 20000]);
    });

    // check bank account route and update user account
    Route::post('/get-account-name', [PaymentController::class, 'handleCheckAccount']);
});

Route::post('/admin/login', [SuperAdminAuthController::class, 'login']);
Route::post('/admin/logout', [SuperAdminAuthController::class, 'logout'])->middleware(['auth:sanctum', 'super-admin']);
   // Admin routes group
   Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('admin')->group(function () {

});

    // Admin routes group
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
     // Super Admin Dashboard Routes
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'getDashboardData']);

        // Product Management Routes
        Route::get('/products', [SuperAdminProductController::class, 'index']); // View Products
        Route::post('/products', [SuperAdminProductController::class, 'store']); // Create Products
        Route::get('/product/{id}', [SuperAdminProductController::class, 'show']); // View Single Product
        Route::put('/product/{id}', [SuperAdminProductController::class, 'update']); // Edit Product
        Route::post('/product/{id}/approve', [SuperAdminProductController::class, 'approve']); // Approve Product
        Route::delete('/product/{id}', [SuperAdminProductController::class, 'destroy']); //Delete Product

        // User Management Routes
        Route::get('/users', [SuperAdminUserController::class, 'index']); // View  Users
        Route::post('/users', [SuperAdminUserController::class, 'store']); // Create  User(s)
        Route::get('/user/{id}', [SuperAdminUserController::class, 'show']); // View Single User
        Route::get('/user/refferer/{referralId}', [SuperAdminUserController::class, 'getReferrerByReferralId']); // View Single User
        Route::put('/user/{id}', [SuperAdminUserController::class, 'update']); // Edit User
        Route::delete('/user/{id}', [SuperAdminUserController::class, 'destroy']); //Delete User

        // Transactions Route
        Route::get('/transactions', [SuperAdminTransactionController::class, 'index']);
        Route::get('/transaction/{id}', [SuperAdminTransactionController::class, 'show']);

        // View all affiliates
        Route::get('/affiliates', [SuperAdminAffiliateController::class, 'index']); // Get all affiliates
        Route::get('/affiliate/{id}', [SuperAdminAffiliateController::class, 'show']); // View individual affiliate
        Route::put('/affiliate/{id}', [SuperAdminAffiliateController::class, 'update']); // Edit affiliate
        Route::delete('/affiliate/{id}', [SuperAdminAffiliateController::class, 'destroy']); // Delete affiliate
        Route::post('/affiliate/create', [SuperAdminAffiliateController::class, 'store']); // Create single affiliate
        Route::post('/affiliate/bulk-upload', [SuperAdminAffiliateController::class, 'bulkUpload']); // Bulk upload affiliates

        // Vendor Routes
        Route::get('/vendors', [SuperAdminVendorController::class, 'index']); // Get all vendors
        Route::get('/vendor/{id}', [SuperAdminVendorController::class, 'show']); // View individual vendor
        Route::put('/vendor/{id}', [SuperAdminVendorController::class, 'update']); // Edit vendor
        Route::delete('/vendor/{id}', [SuperAdminVendorController::class, 'destroy']); // Delete vendor

        // Dashboard Route
        // Route::get('/dashboard-data', [SuperAdminDashboardController::class, 'getDashboardData']);
});
