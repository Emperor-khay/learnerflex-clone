<?php

use App\Http\Controllers\Auths\LoginController;
use App\Http\Controllers\Auths\LogoutController;
use App\Http\Controllers\Auths\RegisterController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Vendor\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/status/{status}', [ProductController::class, 'getApprovedProducts']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/product/{vendor}/digital/create', [ProductController::class, 'createDigitalProduct']);
    Route::post('/product/{vendor}/other/create', [ProductController::class, 'createOtherProduct']);
    Route::patch('/products/{product}/update', [ProductController::class, 'edit']);
    Route::delete('/products/{product}/delete', [ProductController::class, 'destroy']);
});
