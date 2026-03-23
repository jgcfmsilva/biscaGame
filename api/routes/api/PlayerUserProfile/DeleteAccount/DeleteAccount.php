<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PlayerUserProfile\DeleteAccount\DeleteAccountController;

Route::middleware(['auth:sanctum', 'player'])->group(function () {

    Route::post('/player/profile/{id}/delete-request', [DeleteAccountController::class, 'requestAccountDeletion'])
            ->middleware('verified');

});

Route::get('/player/profile/{id}/delete-confirm', [DeleteAccountController::class, 'validateAccountDeletionLink']);

Route::post('/player/profile/{id}/delete-confirm', [DeleteAccountController::class, 'confirmAccountDeletion'])
    ->name('player.profile.delete.confirm');
