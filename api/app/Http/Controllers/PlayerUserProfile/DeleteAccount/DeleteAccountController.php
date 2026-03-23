<?php

namespace App\Http\Controllers\PlayerUserProfile\DeleteAccount;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

use App\Models\User;

use App\Http\Requests\PlayerUserProfile\DeleteAccount\ConfirmAccountDeletionRequest;
use App\Http\Requests\PlayerUserProfile\DeleteAccount\RequestAccountDeletionRequest;
use App\Http\Requests\PlayerUserProfile\DeleteAccount\ValidateAccountDeletionLinkRequest;

use App\Notifications\PlayerUserProfile\DeleteAccount\QueueAccountDeletionConfirmationNotification;
use App\Notifications\PlayerUserProfile\DeleteAccount\QueueAccountDeletedNotification;


class DeleteAccountController extends Controller
{
     public function validateAccountDeletionLink(ValidateAccountDeletionLinkRequest $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Link válido. Podes prosseguir com a eliminação.',
        ]);
    }

    public function requestAccountDeletion(RequestAccountDeletionRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $signedUrl = URL::temporarySignedRoute(
            'player.profile.delete.confirm',
            now()->addMinutes(60),
            ['id' => $user->id]
        );

        $frontendBase = rtrim(config('app.frontend_url') ?? env('FRONTEND_URL') ?? config('app.url'), '/');
        $query = parse_url($signedUrl, PHP_URL_QUERY);
        $frontendLink = $frontendBase . '/confirmAccountDelete/' . $user->id . ($query ? '?' . $query : '');

        $user->notify(new QueueAccountDeletionConfirmationNotification($frontendLink));

        // Deixar tempo para o frontend mostrar a mensagem antes de terminar a sessão
        // (o frontend assume 3 segundos de countdown).

        return response()->json([
            'success' => true,
            'message' => 'Enviámos um email com o link para confirmar a eliminação da conta. A sua sessão será terminada em 3 segundos.',
            'logout_in_seconds' => 3,
        ]);
    }

    public function confirmAccountDeletion(ConfirmAccountDeletionRequest $request, int $id): JsonResponse
    {
        $user = $request->targetUser();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password incorreta.',
            ], 422);
        }

        $user->notify(new QueueAccountDeletedNotification());

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conta eliminada com sucesso.',
        ]);
    }
}
