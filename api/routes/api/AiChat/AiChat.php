<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AiChat\AiChatController;

Route::post('/chat/ask', [AiChatController::class, 'ask']);
