<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\System\SystemStatusController;

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('admin/system/status', [SystemStatusController::class, 'index']);
});
