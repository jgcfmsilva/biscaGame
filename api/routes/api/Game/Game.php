<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Game\GameController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/games', [GameController::class, 'createGame']);
    Route::post('/games/quick', [GameController::class, 'createQuickGame']);
    Route::get('/games/pending-mine', [GameController::class, 'myPending']);
});
