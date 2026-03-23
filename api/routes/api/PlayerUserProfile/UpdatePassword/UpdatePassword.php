<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PlayerUserProfile\UpdatePassword\UpdatePasswordController;

Route::middleware(['auth:sanctum', 'player'])->group(function () {

    Route::patch('/player/profile/{id}/password', [UpdatePasswordController::class, 'updatePassword'])
        ->middleware('verified');

});
