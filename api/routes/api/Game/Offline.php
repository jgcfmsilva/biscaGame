<?php

use App\Http\Controllers\Game\OfflineGameController;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;

Route::middleware([StartSession::class])->prefix('offline')->group(function () {
    Route::post('/start', [OfflineGameController::class, 'start']);
    Route::post('/reconnect', [OfflineGameController::class, 'reconnect']);
    Route::post('/play-card', [OfflineGameController::class, 'playCard']);
    Route::post('/resolve-round', [OfflineGameController::class, 'resolveRound']);
    Route::post('/resign', [OfflineGameController::class, 'resign']);
});
