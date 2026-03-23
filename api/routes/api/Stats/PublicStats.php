<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Stats\PublicStatsController;

Route::get('/public/stats', PublicStatsController::class);
