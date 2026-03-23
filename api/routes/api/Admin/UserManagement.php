<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserInfo\UserManagementController;

Route::middleware(['auth:sanctum', 'admin', 'verified'])->group(function () {
    // Listar todos os utilizadores
    Route::get('/admin/users', [UserManagementController::class, 'index']);

    // Apagar utilizador (mantendo histórico)
    Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy']);
});
