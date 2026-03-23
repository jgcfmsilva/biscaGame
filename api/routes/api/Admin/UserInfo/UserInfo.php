<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\UserInfo\UserInfoController;

Route::middleware(['auth:sanctum', 'admin', 'verified'])->group(function () {
    Route::get('/admin/users/{id}', [UserInfoController::class, 'getUser']);
    Route::patch('/admin/users/{id}/block', [UserInfoController::class, 'blockUser']);
    Route::patch('/admin/users/{id}/unblock', [UserInfoController::class, 'unblockUser']);
});
