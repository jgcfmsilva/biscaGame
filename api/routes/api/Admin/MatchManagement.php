<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Match\GlobalMatchController;

Route::middleware(['auth:sanctum', 'admin', 'verified'])->group(function () {
    // Listar partidas
    Route::get('/admin/matches', [GlobalMatchController::class, 'index']);
    // Detalhe de uma partida
    Route::get('/admin/matches/{id}', [GlobalMatchController::class, 'show']);
});
