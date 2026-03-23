<?php 

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Auth\VerifyEmail\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyEmail\ResendVerificationController;


Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['throttle:6,1'])   
    ->name('verification.verify');

Route::post('/email/resend', ResendVerificationController::class)
    ->middleware(['auth:sanctum', 'throttle:3,1'])
    ->name('verification.resend');
