<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Interfaces\TransactionRepositoryInterface;
use Illuminate\Support\Collection; // Certifique-se de importar Collection
use App\Models\Balance; // Importe o modelo Balance

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function find(string $id): ?Transaction
    {
        return Transaction::find($id);
    }

    public function findByRelatedId(string $relatedId): ?Transaction
    {
        return Transaction::where('related_id', $relatedId)->first();
    }

    public function allByUser(string $userId)
    {
        return Transaction::with(['user', 'toUser']) // Eager load the user and toUser relationships
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('to_user_id', $userId);
            })
            ->latest()
            ->get();
    }


    public function allByRelatedId(string $relatedId)
    {
        return Transaction::where('related_id', $relatedId)->get();
    }

    public function listByUser(string $userId, array $filters = [], int $limit = 10)
    {
        $query = Transaction::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('to_user_id', $userId);
            });

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['data_inicial'])) {
            $query->whereDate('created_at', '>=', $filters['data_inicial']);
        }

        if (!empty($filters['data_final'])) {
            $query->whereDate('created_at', '<=', $filters['data_final']);
        }

        // return $query->orderByDesc('created_at')->limit($limit)->get();

        return $query->orderByDesc('created_at')->paginate($limit);
    }


    /**
     * Retorna o saldo atual e os somatórios de transações por tipo para um usuário.
     */
    public function getTransactionSummary(string $userId): array
    {
        // Saldo atual
        $balance = Balance::where('user_id', $userId)->first();
        $currentBalance = $balance ? (float) $balance->amount : 0.00;

        // Soma de depósitos
        $totalDeposits = Transaction::where('user_id', $userId)
            ->where('type', 'deposit')
            ->sum('value');

        // Soma de valores recebidos de outros usuários (onde o user_id é o recebedor)
        $totalReceived = Transaction::where('user_id', $userId)
            ->where('type', 'receive')
            ->sum('value');

        // Soma de valores enviados para outros usuários (onde o user_id é o remetente)
        $totalSent = Transaction::where('user_id', $userId)
            ->where('type', 'transfer')
            ->sum('value');

        return [
            'current_balance' => (float) $currentBalance,
            'total_deposits' => (float) $totalDeposits,
            'total_received' => (float) $totalReceived,
            'total_sent' => (float) $totalSent,
        ];
    }
}
