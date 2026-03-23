<?php

namespace App\Http\Controllers\Admin\DeletingAdmins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeletingAdmins\DeleteAdminRequest;
use Illuminate\Http\JsonResponse;

class DeleteAdminController extends Controller
{
    public function destroy(DeleteAdminRequest $request, int $id): JsonResponse
    {
        $admin = $request->targetAdmin();

        // Remove active tokens before force delete
        $admin->tokens()->delete();
        $admin->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Administrador eliminado com sucesso.',
        ]);
    }
}
