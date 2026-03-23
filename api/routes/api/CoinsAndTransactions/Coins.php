<?php

use App\Http\Controllers\CoinsAndTransactions\CoinController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coins/balance',      [CoinController::class, 'balance']);
    Route::get('/coins/transactions', [CoinController::class, 'transactions']);
    Route::post('/coins/buy',         [CoinController::class, 'buy']);
});
