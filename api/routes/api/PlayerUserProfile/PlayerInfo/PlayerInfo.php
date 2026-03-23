<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PlayerUserProfile\PlayerInfo\PlayerInfoController;

Route::get('/player/profile/public/{id}', [PlayerInfoController::class, 'getPlayerByIdPublic']);
Route::get('/player/avatar/{path}', [PlayerInfoController::class, 'avatar'])
    ->where('path', '.*');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile/self', function (Request $request) {
        return $request->user();
    });
});

Route::middleware(['auth:sanctum', 'player'])->group(function () {
    Route::get('/player/profile/self', function (Request $request) {
        return $request->user();
    });

    Route::get('/player/profile/{id}', [PlayerInfoController::class, 'getPlayerByIdPrivate']);

    Route::put('/player/profile/{id}', [PlayerInfoController::class, 'updatePersonalInfo'])
        ->middleware('verified');
});
