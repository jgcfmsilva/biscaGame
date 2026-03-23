<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoinsAndTransactions\CoinTransactionController;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // 1. User: Ver o meu histórico
    Route::get('/transactions', [CoinTransactionController::class, 'index']);

    // 2. User: Comprar moedas
    Route::post('/transactions', [CoinTransactionController::class, 'store']);

    // 3. Admin: Ver histórico global
    Route::get('/admin/transactions', [CoinTransactionController::class, 'showAll']);

    // 4. Admin: Estatísticas Globais (G6)
    Route::get('/admin/statistics', [CoinTransactionController::class, 'statistics']);
    // 5. Admin: Oferecer Moedas (Grant)
    Route::post('/admin/transactions/grant', [CoinTransactionController::class, 'grant']);

});