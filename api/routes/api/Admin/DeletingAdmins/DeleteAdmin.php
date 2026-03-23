<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeletingAdmins\DeleteAdminController;

Route::middleware(['auth:sanctum', 'admin', 'verified'])->group(function () {
    Route::delete('/admin/admins/{id}', [DeleteAdminController::class, 'destroy']);
});
