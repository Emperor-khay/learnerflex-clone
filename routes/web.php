<?php

use App\Http\Controllers\Flutterwave\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment/callback', [PaymentController::class, 'handleOnboardCallback']);

Route::get('/payment/market-access/callback', [PaymentController::class, 'handleMarketAccessCallback']);
