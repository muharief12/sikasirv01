<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentMethodController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);
Route::apiResource('products', ProductController::class)->middleware(['auth:sanctum']);
Route::get('products/barcode/{barcode}', [ProductController::class, 'showByBarcode'])->middleware(['auth:sanctum']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('payment_methods', [PaymentMethodController::class, 'index'])->middleware('auth:sanctum');
Route::get('orders', [OrderController::class, 'index'])->middleware('auth:sanctum');
