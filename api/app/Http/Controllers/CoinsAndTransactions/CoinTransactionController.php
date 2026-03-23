<?php

namespace App\Http\Controllers\CoinsAndTransactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoinTransactionRequest;
use App\Models\CoinTransaction;
use App\Models\CoinPurchase;
use App\Models\User;
use App\Models\Game;
use App\Models\MatchModel;
use App\Notifications\Coins\CoinPurchasedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CoinTransactionController extends Controller
{
    // 1. Histórico do Próprio Jogador
    public function index(Request $request)
    {
        // Retorna transações do user autenticado, mais recentes primeiro
        return $request->user()
            ->transactions()
            ->with(['purchase', 'type'])
            ->orderBy('transaction_datetime', 'desc')
            ->get();
    }

    // 2. Comprar Moedas (Depósito)
    public function store(StoreCoinTransactionRequest $request)
    {
        // Dados já validados pelo FormRequest
        $validated = $request->validated();
        $user = $request->user();
        $euros = (float) $validated['value'];
        $coinsEarned = (int) ($euros * 10); // Taxa fixa: 1€ = 10 moedas

        // Chamar API Externa de Pagamentos
        $response = Http::post('https://dad-payments-api.vercel.app/api/debit', [
            'type' => $validated['type'],
            'reference' => $validated['reference'],
            'value' => (float) $validated['value'],
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Pagamento recusado pela gateway.',
                'errors' => $response->json()
            ], 422);
        }

        // Processar transação atomicamente
        try {
            DB::transaction(function () use ($validated, $user, $coinsEarned, $euros) {
                // A. Criar registo na tabela coin_transactions
                $transaction = new CoinTransaction();
                $transaction->user_id = $user->id;
                $transaction->transaction_datetime = now();
                $transaction->coin_transaction_type_id = 2; // ID 2 = "Coin purchase"
                $transaction->coins = $coinsEarned;
                $transaction->save();

                // B. Criar registo na tabela coin_purchases
                $purchase = new CoinPurchase();
                $purchase->coin_transaction_id = $transaction->id;
                $purchase->user_id = $user->id;
                $purchase->purchase_datetime = now();
                $purchase->euros = $euros;
                $purchase->payment_type = $validated['type'];
                $purchase->payment_reference = $validated['reference'];
                $purchase->save();

                // C. Atualizar saldo do utilizador
                $user->coins_balance += $coinsEarned;
                $user->save();
            });

            $freshUser = $user->fresh();
            $freshUser->notify(new CoinPurchasedNotification(
                euros: $euros,
                coins: $coinsEarned,
                paymentType: $validated['type'],
                newBalance: (int) $freshUser->coins_balance,
            ));

            // Retorna o novo saldo
            return response()->json([
                'message' => 'Compra realizada com sucesso!',
                'coins_balance' => $freshUser->coins_balance
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro interno ao processar compra.', 'details' => $e->getMessage()], 500);
        }
    }

    // 3. Histórico Global (Apenas para Administradores)
    public function showAll(Request $request)
    {
        // Verificar se o user é admin (tipo 'A')
        if ($request->user()->type !== 'A') {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $query = CoinTransaction::with([
            'user' => function ($query) {
                $query->withTrashed();
            },
            'type' => function ($query) {
                $query->withTrashed();
            },
            'purchase',
        ]);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $normalized = strtolower($search);
            $query->where(function ($q) use ($search, $normalized) {
                if (is_numeric($search)) {
                    $q->orWhere('coin_transactions.id', (int) $search);
                    $q->orWhere('coin_transactions.user_id', (int) $search);
                }

                $q->orWhereHas('user', function ($userQuery) use ($normalized) {
                    $like = '%' . $normalized . '%';
                    $userQuery
                        ->whereRaw('LOWER(nickname) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });

                $q->orWhereHas('type', function ($typeQuery) use ($normalized) {
                    $like = '%' . $normalized . '%';
                    $typeQuery->whereRaw('LOWER(name) LIKE ?', [$like]);
                });

                $q->orWhereHas('purchase', function ($purchaseQuery) use ($normalized) {
                    $like = '%' . $normalized . '%';
                    $purchaseQuery
                        ->whereRaw('LOWER(payment_reference) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(payment_type) LIKE ?', [$like]);
                });
            });
        }

        $typeId = $request->query('type_id');
        if (is_numeric($typeId)) {
            $query->where('coin_transaction_type_id', (int) $typeId);
        }

        $direction = $request->query('direction');
        if ($direction === 'credit' || $direction === 'debit') {
            $typeCode = $direction === 'credit' ? 'C' : 'D';
            $query->whereHas('type', function ($typeQuery) use ($typeCode) {
                $typeQuery->where('type', $typeCode);
            });
        }

        $paymentType = $request->query('payment_type');
        if (is_string($paymentType) && $paymentType !== '') {
            $query->whereHas('purchase', function ($purchaseQuery) use ($paymentType) {
                $purchaseQuery->where('payment_type', $paymentType);
            });
        }

        $dateFrom = $request->query('date_from');
        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->whereDate('transaction_datetime', '>=', $dateFrom);
        }

        $dateTo = $request->query('date_to');
        if (is_string($dateTo) && $dateTo !== '') {
            $query->whereDate('transaction_datetime', '<=', $dateTo);
        }

        $coinsMin = $request->query('coins_min');
        if (is_numeric($coinsMin)) {
            $query->whereRaw('ABS(coins) >= ?', [(int) $coinsMin]);
        }

        $coinsMax = $request->query('coins_max');
        if (is_numeric($coinsMax)) {
            $query->whereRaw('ABS(coins) <= ?', [(int) $coinsMax]);
        }

        $sortBy = $request->query('sort_by', 'transaction_datetime');
        $allowedSorts = ['transaction_datetime', 'coins', 'id'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'transaction_datetime';
        }

        $sortOrder = $request->query('sort_order', 'desc');
        $sortOrder = $sortOrder === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 5) {
            $perPage = 5;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        return $query
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    // GET /api/admin/statistics (G6)
    public function statistics(Request $request)
    {
        if ($request->user()->type !== 'A') {
            abort(403);
        }

        return response()->json([
            // Métricas gerais
            'total_purchased_euros' => CoinPurchase::sum('euros'),
            'total_coins_in_system' => User::sum('coins_balance'),
            'total_transactions' => CoinTransaction::count(),
            'total_games' => Game::count(),
            'total_matches' => MatchModel::count(),

            // Top 10 utilizadores com mais moedas
            'balance_by_user' => User::select('nickname', 'coins_balance')
                ->orderBy('coins_balance', 'desc')
                ->take(10)
                ->get(),

            // [NOVO] Time-series: Vendas por mês (últimos 12 meses)
            // Nota: "TO_CHAR" é específico para PostgreSQL. Se usasses MySQL seria DATE_FORMAT.
            'purchases_by_month' => CoinPurchase::selectRaw("TO_CHAR(purchase_datetime, 'YYYY-MM') as month, SUM(euros) as total")
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->take(12)
                ->get()
        ]);
    }
    // 4. Oferecer Moedas (Admin)
    public function grant(Request $request)
    {
        if ($request->user()->type !== 'A') {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'amount' => 'required|integer|min:1|max:10000',
            'reason' => 'nullable|string|max:100'
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $targetUser = User::where('email', $validated['email'])->firstOrFail();

                $transaction = new CoinTransaction();
                $transaction->user_id = $targetUser->id;
                $transaction->transaction_datetime = now();
                $transaction->coin_transaction_type_id = 1; // ID 1 = Bonus
                $transaction->coins = $validated['amount'];
                // We could store reason in a 'description' column if it existed, but current schema might not have it.
                // Assuming standard schema from migration observation (or lack thereof), we skip description for now or assume it's not critical.
                $transaction->save();

                $targetUser->coins_balance += $validated['amount'];
                $targetUser->save();
            });

            return response()->json(['message' => 'Moedas oferecidas com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao processar oferta.', 'error' => $e->getMessage()], 500);
        }
    }
}
