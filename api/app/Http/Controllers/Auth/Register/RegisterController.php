<?php

namespace App\Http\Controllers\Auth\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Register\RegisterRequest;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class RegisterController extends Controller
{

    public function __construct(private CoinService $coinService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $photoFilename = null;
        if ($request->hasFile('photo')) {
            $storedPath = $request->file('photo')->store('avatars', 'public');
            $photoFilename = $storedPath;
        }

        $user = User::create([
            'name'                  => $data['name'] ?? $data['nickname'],
            'email'                 => $data['email'],
            'nickname'              => $data['nickname'],
            'password'              => $data['password'],
            'photo_avatar_filename' => $photoFilename,
        ]);

        $this->coinService->applyWelcomeBonusIfMissing($user, 10, 'Bonus');

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Conta criada. Verifica o teu email para a confirmares.',
            'user'    => $user->only(['id','name','email','nickname','photo_avatar_filename']),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        if ($user->blocked) {
            return response()->json(['message' => 'Conta bloqueada'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'nickname' => 'sometimes|string|max:50|unique:users,nickname,' . $user->id,
            'password' => ['sometimes', 'string', 'min:3'],
        ]);

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['nickname'])) {
            $user->nickname = $data['nickname'];
        }
        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json($user);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'confirm' => 'required|string',
        ]);

        if ($request->input('confirm') !== $user->nickname) {
            return response()->json(['message' => 'Confirmação inválida'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Conta removida com sucesso']);
    }
}
