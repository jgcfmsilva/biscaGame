<?php

namespace App\Http\Controllers\Admin\ActiveAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Services\ActiveAdmins\ActiveAdminCacheService;

class ActiveAdminController extends Controller
{
    public function __construct(private readonly ActiveAdminCacheService $activeAdminCache)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->activeAdminCache->listActive());
    }
}
