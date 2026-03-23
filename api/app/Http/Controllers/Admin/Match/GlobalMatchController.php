<?php

namespace App\Http\Controllers\Admin\Match;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalMatchController extends Controller
{
    /**
     * Listar partidas (matches) com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = strtolower($request->get('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = MatchModel::with(['player1', 'player2', 'winner']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('player1', function ($q2) use ($search) {
                        $q2->where('nickname', 'like', "%{$search}%");
                    })
                    ->orWhereHas('player2', function ($q2) use ($search) {
                        $q2->where('nickname', 'like', "%{$search}%");
                    });
            });
        }

        $allowedSort = ['id', 'created_at', 'ended_at', 'stake'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Mostrar detalhe de uma partida.
     */
    public function show($id): JsonResponse
    {
        $match = MatchModel::with(['player1', 'player2', 'winner', 'games'])->find($id);
        if (!$match) {
            return response()->json(['message' => 'Partida não encontrada.'], 404);
        }

        return response()->json($match);
    }
}
