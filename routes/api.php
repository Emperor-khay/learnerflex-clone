<?php

use App\Http\Controllers\AffiliateController;
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
use App\Http\Controllers\SecondVendorController;
use App\Http\Controllers\SuperAdmin\SuperAdminAffiliateController;
use App\Http\Controllers\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminProductController;
use App\Http\Controllers\SuperAdmin\SuperAdminTransactionController;
use App\Http\Controllers\SuperAdmin\SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\SuperAdminVendorController;

//Public Routes

Route::post('/payment/make-payment', [PaystackController::class, 'make_payment']);
// Route for handling the payment callback
Route::post('/payment/callback', [PaystackController::class, 'payment_callback'])->name('callback');
Route::post('/ebook-mentorship/make-payment', [PayStackEbookController::class, 'make_payment']);
// Route for handling the payment callback
Route::post('/ebook-mentorship/callback', [PayStackEbookController::class, 'paymentCallback'])->name('callback');
// unlock market
//one time payment
Route::post('/marketplace/payment', [MarketplacePaymentController::class,'payment'])->name('marketplace.payment');
Route::get('/marketplace/payment/callback', [MarketplacePaymentController::class, 'payment_callback'])->name('marketplace.payment.callback');

Route::post('/oh', [MarketplacePaymentController::class,'redirectToGateway'])->name('oh');
Route::get('/ohyes', [MarketplacePaymentController::class, 'handleGatewayCallback'])->name('ohyes');

//User Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'attemptUser']);
    Route::post('/logout', [LogoutController::class, 'logout']);
});
//Admin Authentication
Route::post('/admin/login', [SuperAdminAuthController::class, 'login']);

// Affiliate routes group
Route::middleware(['auth:sanctum', 'role:affiliate'])->prefix('affiliate')->group(function () {

    Route::get('/dashboard', [AffiliateController::class, 'affiliateDashboardMetrics']);

    // marketplace
    Route::get('/{id}/product/{reffer_id}', [ProductController::class, 'getProduct']);

    //profile routes
    Route::post('/update/image', [UserController::class, 'handleUserImage']);
    Route::post('/update/profile', [UserController::class, 'handleUserProfile']);

    //vendor requesting to be a vendor
    Route::post('/request-vendor', [VendorController::class, 'sendVendorRequest']);
    
    // withdrawals
    Route::get('/request/withdrawal', [WithdrawalController::class, 'index']);
    Route::get('/withdrawals', [WithdrawalController::class, 'WithdrawRecord']);
    //transactions
    Route::get('/transactions', [UserController::class, 'transactions']);
    //products routes
    Route::get('/products', [AffiliateController::class, 'affiliateproducts']);
    
    Route::get('/products/{id}', [AffiliateController::class, 'showAffiliateProducts']);
    
    Route::post('/unlock/market', [AffiliateController::class, 'unlockMarketAccess']);
    Route::get('/unlock/market/callback', [AffiliateController::class, 'marketAccessCallback'])->name('unlock.market.callback');

    //password resetting routes
    Route::post('password/reset-link', [PasswordResetController::class, 'sendPasswordResetLink']);
    Route::post('password/new-password', [NewPasswordReset::class, 'resetPassword']);


    // //gets sales data
    // Route::get('/affiliate/sales', [UserController::class, 'salesAffiliate']);
    // // check bank account route and update user account
    // Route::post('/get-account-name', [PaymentController::class, 'handleCheckAccount']);
    // Route::get('/products/status/{status}', [ProductController::class, 'getApprovedProducts']);
    //view all products from a particular vendor
    // Route::get('product/view-product/{vendor_id}/', [ProductController::class, 'viewProductsByVendor']);
    //get product by id
    // Route::get('product/view-product/{vendor_id}/{product_id}', [ProductController::class, 'viewProductByVendor']);
});


// Vendor routes group
Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor')->group(function () {
    Route::get('/dashboard', [SecondVendorController::class, 'vendorDashboardMetrics']);
    Route::get('/affiliate/dashboard', [SecondVendorController::class, 'vendorAffiliiateDashboardMetrics']);

    //profile routes
    Route::post('/update/image', [UserController::class, 'handleUserImage']);
    Route::post('/update/profile', [UserController::class, 'handleUserProfile']);

    Route::get('/users/{user}/vendor', [VendorController::class, 'index']);

    // check bank account route and update user account
    Route::post('/get-account-name', [PaymentController::class, 'handleCheckAccount']);
    //view all products from a particular vendor
    Route::get('product/view-product/{vendor_id}/', [ProductController::class, 'viewProductsByVendor']);
    //get product by id
    Route::get('product/view-product/{vendor_id}/{product_id}', [ProductController::class, 'viewProductByVendor']);
    Route::delete('/product/delete/{id}', [ProductController::class, 'deleteProduct']);

    // products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/status/{status}', [ProductController::class, 'getApprovedProducts']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/product/digital/create', [ProductController::class, 'createDigitalProduct']);
    Route::post('/product/other/create', [ProductController::class, 'createOtherProduct']);
    Route::patch('/products/{product}/update', [ProductController::class, 'edit']);
    Route::delete('/products/{product}/delete', [ProductController::class, 'destroy']);

    Route::get('/{id}/transactions', [VendorController::class, 'getVendorSales']);
    Route::post('/students', [VendorController::class, 'students']);
    Route::post('/product-performance', [VendorController::class, 'productPerformance']);
    Route::get('/affiliate-details/{aff_id}', [VendorController::class, 'getAffDetails']);

    //add new product
    Route::post('product/add-product', [ProductController::class, 'addProduct']);

    //gets sales data
    Route::get('/affiliate/sales', [UserController::class, 'salesAffiliate']);

    // withdrawals
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::get('/withdrawals/amount', [WithdrawalController::class, 'userWithdrawSum']);

    //password resetting routes
    Route::post('password/reset-link', [PasswordResetController::class, 'sendPasswordResetLink']);
    Route::post('password/new-password', [NewPasswordReset::class, 'resetPassword']);
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

    Route::prefix('users')->group(function () {
        Route::get('/', [SuperAdminUserController::class, 'index']); // View  Users
        Route::get('/{role}/{id}', [SuperAdminUserController::class, 'showuser']); // View by role and id
        Route::post('/{role}', [SuperAdminUserController::class, 'store']); // Create role-based entity
        Route::put('/{role}/{id}', [SuperAdminUserController::class, 'update']); // Update by role and id
        Route::delete('/{role}/{id}', [SuperAdminUserController::class, 'destroy']); // Delete by role and id
    });


    // Route::get('/user/refferer/{referralId}', [SuperAdminUserController::class, 'getReferrerByReferralId']); // View Single User

    // Transactions Route
    Route::get('/transactions', [SuperAdminTransactionController::class, 'index']);

    Route::post('/affiliate/bulk-upload', [SuperAdminAffiliateController::class, 'bulkUpload']); // Bulk upload affiliates
    //set affiliates to vendor
    Route::patch('/user/accept-vendor-request/{id}', [UserController::class, 'upgradeAffiliateToVendor']);

    Route::post('/vendor/create', [VendorController::class, 'store']);
    Route::delete('/vendors/{vendor}/delete', [VendorController::class, 'delete']);

    Route::post('/admin/logout', [SuperAdminAuthController::class, 'logout']);
});


//test routes
Route::get('/users', [UserController::class, 'index']);
//test endpoints
Route::get('/test', function () {
    return response()->json(['message' => 'Checked successful']);
});
//not sure what these are used for
// Route::post('/user/get-balance', [UserController::class, 'getBalance']);
// Route::post('user/total-aff-sales', [UserController::class, 'totalSaleAff']);
// Route::post('/request-withdrawal', [UserController::class, 'requestWithdrawal']);