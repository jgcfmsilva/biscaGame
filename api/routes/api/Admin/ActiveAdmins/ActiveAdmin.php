<?php

use App\Http\Controllers\Admin\ActiveAdmin\ActiveAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/active-admins', [ActiveAdminController::class, 'index']);
