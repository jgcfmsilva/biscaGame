<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\Register\RegisterController;

Route::post('/register', [RegisterController::class, 'register']);