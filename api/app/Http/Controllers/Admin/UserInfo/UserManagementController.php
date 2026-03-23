<?php

namespace App\Http\Controllers\Admin\UserInfo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Aqui vou buscar todos os utilizadores (Jogadores e Admins) com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Por defeito, incluir eliminados (soft deletes) quando não há filtro explícito
        if (! $request->filled('deleted')) {
            $query->withTrashed();
        }

        // 1. Filtro por Tipo (A = Admin, P = Jogador)
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 2. Filtro pela pesquisa (tento encontrar por id, nome, alcunha ou email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }
                $q->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2.1 Filtro por estado (bloqueado/ativo)
        if ($request->has('blocked')) {
            $query->where('blocked', $request->boolean('blocked'));
        }

        // 2.2 Filtro por eliminados (soft delete)
        if ($request->filled('deleted')) {
            if ($request->boolean('deleted')) {
                $query->withTrashed()->whereNotNull('deleted_at');
            } else {
                $query->whereNull('deleted_at');
            }
        }

        // 2.3 Filtro por admins com password pendente
        if ($request->filled('must_change_password')) {
            $query->where('custom->must_change_password', $request->boolean('must_change_password'));
        }

        // 3. Ordenação simples
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 4. Paginação dos resultados
        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Apagar um utilizador (Jogador ou Admin) mas mantendo o histórico.
     * Não me posso apagar a mim mesmo.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        // Não deixar apagar a minha própria conta
        if ($currentUser->id === $id) {
            return response()->json(['message' => 'Não podes apagar a tua própria conta.'], 403);
        }

        $userToDelete = User::findOrFail($id);

        // Cancelar as sessões deste utilizador
        $userToDelete->tokens()->delete();

        $userToDelete->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilizador eliminado com sucesso.',
            'user' => $userToDelete
        ]);
    }
}
