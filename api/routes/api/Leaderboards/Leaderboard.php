<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Leaderboards\LeaderboardController;

Route::get('/leaderboard/global', [LeaderboardController::class, 'getGlobal']);

Route::middleware('auth:sanctum')->get('/leaderboard/personal', [LeaderboardController::class, 'getPersonal']);