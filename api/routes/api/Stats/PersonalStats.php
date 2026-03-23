<?php

use App\Http\Controllers\Stats\PersonalStatsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/player/stats/personal', PersonalStatsController::class);
});
