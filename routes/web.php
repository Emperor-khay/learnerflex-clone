<?php

use App\Http\Controllers\Flutterwave\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
//        // web.php (or api.php, depending on your setup)
//        Route::get('/verify-vendor/{id}', function ($id) {
//         return view('verify-vendor', compact('id'));
//     });
// });


Route::get('/payment/callback', [PaymentController::class, 'handleOnboardCallback']);

Route::get('/payment/market-access/callback', [PaymentController::class, 'handleMarketAccessCallback']);