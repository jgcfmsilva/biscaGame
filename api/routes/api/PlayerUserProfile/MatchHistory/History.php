<?php

use App\Http\Controllers\PlayerUserProfile\MatchHistory\PlayerMatchHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/player/matches/history', [PlayerMatchHistoryController::class, 'index']);
    Route::get('/player/matches', [PlayerMatchHistoryController::class, 'paginated']);
});
