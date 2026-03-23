<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Game\MatchController;

Route::middleware('auth:sanctum')->prefix('matches')->group(function () {
    Route::post('/', [MatchController::class, 'create']);
    Route::get('/{match}', [MatchController::class, 'show']);
    Route::post('/{match}/join', [MatchController::class, 'join']);
    Route::post('/{match}/play-card', [MatchController::class, 'playCard']);
    Route::post('/{match}/resign', [MatchController::class, 'resign']);
    Route::post('/{match}/next-game', [MatchController::class, 'nextGame']);
    Route::delete('/{match}', [MatchController::class, 'cancel']);
});
