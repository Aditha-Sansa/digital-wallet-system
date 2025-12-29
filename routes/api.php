<?php

use App\Http\Controllers\WalletCreditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', [WalletCreditController::class, 'test']);
Route::get('/generate-credits', [WalletCreditController::class, 'generateWalletCredits']);
Route::post('/process-credits', [WalletCreditController::class, 'store']);

