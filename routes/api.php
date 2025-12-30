<?php

use App\Http\Controllers\WalletCreditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/wallet/bulk-credit', [WalletCreditController::class, 'store']);
Route::post('/wallet/bulk-credit/retry', [WalletCreditController::class, 'retry']);
