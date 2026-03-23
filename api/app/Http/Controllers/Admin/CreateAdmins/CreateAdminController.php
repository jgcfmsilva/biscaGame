<?php

namespace App\Http\Controllers\Admin\CreateAdmins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdmins\CreateAdminRequest;
use App\Models\User;
use App\Notifications\Admin\CreateAdmins\QueueAdminMustChangePasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class CreateAdminController extends Controller
{
    public function store(CreateAdminRequest $request): JsonResponse
    {
        $data = $request->validated();

        $photoFilename = null;
        if ($request->hasFile('photo')) {
            $photoFilename = $request->file('photo')->store('avatars', 'public');
        }

        $admin = User::create([
            'name'                  => $data['name'] ?? $data['nickname'],
            'email'                 => $data['email'],
            'nickname'              => $data['nickname'],
            'password'              => $data['password'],
            'photo_avatar_filename' => $photoFilename,
            'type'                  => 'A',
            'custom'                => ['must_change_password' => true],
        ]);

        $token = Password::createToken($admin);
        $admin->notify(new QueueAdminMustChangePasswordNotification($token));

        return response()->json([
            'success' => true,
            'message' => 'Administrador criado. Tem de alterar a password antes do primeiro acesso.',
            'admin'   => $admin->only([
                'id',
                'name',
                'email',
                'nickname',
                'photo_avatar_filename',
                'type',
                'custom',
            ]),
        ], 201);
    }
}
