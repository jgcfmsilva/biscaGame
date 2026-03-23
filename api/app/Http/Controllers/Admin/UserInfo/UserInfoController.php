<?php

namespace App\Http\Controllers\Admin\UserInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\Admin\UserInfo\BlockUserRequest;
use App\Http\Requests\Admin\UserInfo\GetUserRequest;
use App\Http\Requests\Admin\UserInfo\UnblockUserRequest;

use App\Notifications\Admin\UserInfo\QueueUserBlockedNotification;
use App\Notifications\Admin\UserInfo\QueueUserUnblockedNotification;

class UserInfoController extends Controller
{
    public function getUser(GetUserRequest $request, int $id): JsonResponse
    {
        $user = $request->targetUser();

        return response()->json($user);
    }

    public function blockUser(BlockUserRequest $request, int $id): JsonResponse
    {
        $user = $request->targetUser();

        if ($user->blocked) {
            return response()->json([
                'success' => true,
                'message' => 'Utilizador já estava bloqueado.',
                'user'    => $user,
            ]);
        }

        $user->forceFill(['blocked' => true])->save();
        $user->notify(new QueueUserBlockedNotification());
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilizador bloqueado com sucesso.',
            'user'    => $user->fresh(),
        ]);
    }

    public function unblockUser(UnblockUserRequest $request, int $id): JsonResponse
    {
        $user = $request->targetUser();

        if (! $user->blocked) {
            return response()->json([
                'success' => true,
                'message' => 'Utilizador já estava desbloqueado.',
                'user'    => $user,
            ]);
        }

        $user->forceFill(['blocked' => false])->save();
        $user->notify(new QueueUserUnblockedNotification());

        return response()->json([
            'success' => true,
            'message' => 'Utilizador desbloqueado com sucesso.',
            'user'    => $user->fresh(),
        ]);
    }
}
