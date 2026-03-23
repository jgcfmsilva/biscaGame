<?php

namespace App\Http\Controllers\PlayerUserProfile\PlayerInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

use App\Models\User;

use App\Http\Requests\PlayerUserProfile\PlayerInfo\GetPlayerPrivateRequest;
use App\Http\Requests\PlayerUserProfile\PlayerInfo\UpdatePlayerInfoRequest;

use App\Notifications\PlayerUserProfile\PlayerInfo\QueueProfileUpdatedNotification;
use Illuminate\Support\Facades\Storage;

class PlayerInfoController extends Controller
{
    public function getPlayerByIdPrivate(GetPlayerPrivateRequest $request, int $id): JsonResponse
    {
        /** @var User|null $authUser */
        $authUser = $request->user();
        $targetUser = $request->targetUser();

        if ($targetUser->type === 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Utilizadores admin não podem ser obtidos.',
            ], 403);
        }

        $isSelf = $authUser && $authUser->id === $targetUser->id;

        if (!$isSelf) {
            return response()->json($this->publicPlayerPayload($targetUser));
        }

        return response()->json($targetUser);
    }

    public function getPlayerByIdPublic(int $id): JsonResponse
    {
        $targetUser = User::findOrFail($id);

        if ($targetUser->type === 'A') {
            return response()->json([
                'success' => false,
                'message' => 'Utilizadores admin não podem ser obtidos.',
            ], 403);
        }

        return response()->json($this->publicPlayerPayload($targetUser));
    }

    public function updatePersonalInfo(UpdatePlayerInfoRequest $request): JsonResponse
    {
        $authUser = $request->user();
        $targetUser = $request->targetUser();
        $isSelf = $authUser && $authUser->id === $targetUser->id;
        $previousAvatar = $targetUser->photo_avatar_filename;

        $validated = $request->validated();
        $shouldRemoveAvatar = array_key_exists('remove_avatar', $validated)
            && filter_var($validated['remove_avatar'], FILTER_VALIDATE_BOOLEAN);

        $data = collect($validated)
            ->except(['photo', 'remove_avatar'])
            ->toArray();

        $avatarChanged = false;

        if ($shouldRemoveAvatar) {
            $data['photo_avatar_filename'] = null;
            $avatarChanged = true;
        }

        if ($request->hasFile('photo')) {
            $uploadedFile = $request->file('photo');
            $isSameAvatar = false;

            if ($previousAvatar && Storage::disk('public')->exists($previousAvatar)) {
                $currentPath = Storage::disk('public')->path($previousAvatar);
                $currentHash = @hash_file('md5', $currentPath);
                $uploadHash = @hash_file('md5', $uploadedFile->getRealPath());
                $isSameAvatar = $currentHash && $uploadHash && hash_equals($currentHash, $uploadHash);
            }

            if (!$isSameAvatar) {
                $storedPath = $uploadedFile->store('photos_avatars', 'public');
                $data['photo_avatar_filename'] = $storedPath;
                $avatarChanged = true;
            }
        }

        $hasProfileChanges = collect($data)->some(function ($value, $key) use ($targetUser) {
            return $targetUser->{$key} !== $value;
        });

        $hasChanges = $hasProfileChanges || $avatarChanged;

        $emailChangedBySelf = $isSelf
            && array_key_exists('email', $data)
            && $data['email'] !== $targetUser->email;

        if ($emailChangedBySelf) {
            $targetUser->forceFill(['email_verified_at' => null]);
        }

        if (!$hasChanges) {
            return response()->json([
                'success' => true,
                'message' => 'Nenhuma alteração foi aplicada.',
                'user' => $targetUser,
            ]);
        }

        $targetUser->fill($data)->save();

        if ($shouldRemoveAvatar && $previousAvatar && Storage::disk('public')->exists($previousAvatar)) {
            Storage::disk('public')->delete($previousAvatar);
        }

        if (
            isset($data['photo_avatar_filename']) &&
            $previousAvatar &&
            $previousAvatar !== $data['photo_avatar_filename'] &&
            Storage::disk('public')->exists($previousAvatar)
        ) {
            Storage::disk('public')->delete($previousAvatar);
        }

        if ($emailChangedBySelf) {
            $targetUser->sendEmailVerificationNotification();
            $authUser?->currentAccessToken()?->delete();
        }

        if ($hasProfileChanges) {
            $targetUser->notify(new QueueProfileUpdatedNotification());
        }

        $freshUser = $targetUser->fresh();

        $message = $emailChangedBySelf
            ? 'Email atualizado. Enviámos um novo email de verificação e a sessão será terminada.'
            : 'Dados atualizados com sucesso.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'user' => $freshUser,
        ]);
    }

    public function avatar(string $path)
    {
        $path = ltrim($path, '/');
        if (str_contains($path, '..')) {
            return response()->json(['message' => 'Caminho inválido.'], 400);
        }

        $allowed = ['photos_avatars/', 'avatars/'];
        $isAllowed = collect($allowed)->some(fn($prefix) => str_starts_with($path, $prefix));
        if (!$isAllowed) {
            return response()->json(['message' => 'Caminho de avatar inválido.'], 400);
        }

        if (Storage::disk('public')->exists($path)) {
            return response()->file(Storage::disk('public')->path($path));
        }

        // Expanded Fallback Logic:
        // Try to determine the user ID from the filename (format: 00001_...)
        // and serve a deterministic seeder image.
        if (preg_match('/^(\d+)_/', basename($path), $matches)) {
            $userId = (int) $matches[1];
            $user = User::find($userId);

            if ($user) {
                // Determine gender prefix based on nickname convention in seeder
                // UsersSeeder: Mickey -> M, Minnie -> F
                $prefix = 'm';
                if (str_starts_with($user->nickname ?? '', 'Minnie')) {
                    $prefix = 'w';
                } elseif (!str_starts_with($user->nickname ?? '', 'Mickey')) {
                    // Start admins (id <= 10) or others with mix
                    // Admins: 1=M, 2=F, 3=M, 4=M.
                    // Just randomize/hash for others.
                    $prefix = ($userId % 2 === 0) ? 'w' : 'm';
                }

                // Find all available images with that prefix in the public assets
                $seedPath = public_path('assets/avatars');
                $files = glob("$seedPath/{$prefix}_*.jpeg");

                if ($files && count($files) > 0) {
                    // Deterministic selection based on User ID
                    $index = $userId % count($files);
                    $selectedFile = $files[$index];
                    return response()->file($selectedFile);
                }
            }
        }

        // Fallback to anonymous.png if file not found and deterministic logic fails
        $fallbackPath = public_path('assets/img/anonymous.png');
        if (file_exists($fallbackPath)) {
            return response()->file($fallbackPath);
        }

        return response()->json(['message' => 'Avatar não encontrado.'], 404);
    }

    private function publicPlayerPayload(User $user): array
    {
        return [
            'nickname' => $user->nickname,
            'avatar' => $user->photo_avatar_filename,
            'created_at' => $user->created_at,
        ];
    }
}
