<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Game\LobbyController;

Route::middleware('auth:sanctum')->prefix('lobby')->group(function () {
    Route::get('/games',  [LobbyController::class, 'openGames']);
    Route::get('/matches', [LobbyController::class, 'openMatches']);
    Route::get('/active', [LobbyController::class, 'active']);
});
