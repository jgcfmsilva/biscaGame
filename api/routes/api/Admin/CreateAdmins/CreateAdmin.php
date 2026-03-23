<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CreateAdmins\CreateAdminController;

Route::middleware(['auth:sanctum', 'admin', 'verified'])->group(function () {
    Route::post('/admin/admins', [CreateAdminController::class, 'store']);
});
