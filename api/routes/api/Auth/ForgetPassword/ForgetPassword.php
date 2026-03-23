<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Auth\ForgetPassword\RequestPasswordResetController;
use App\Http\Controllers\Auth\ForgetPassword\PasswordResetTokenVerifyController;
use App\Http\Controllers\Auth\ForgetPassword\ResetPasswordController;

Route::post('/password/email', [RequestPasswordResetController::class, 'send']);

Route::get('/password/reset/verify', PasswordResetTokenVerifyController::class);

Route::post('/password/reset', ResetPasswordController::class);
