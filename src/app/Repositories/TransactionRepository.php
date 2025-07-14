<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Interfaces\TransactionRepositoryInterface;

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
        return Transaction::where('user_id', $userId)
            ->orWhere('to_user_id', $userId)
            ->latest()
            ->get();
    }


    public function allByRelatedId(string $relatedId)
    {
        return Transaction::where('related_id', $relatedId)->get();
    }

    public function listByUser(string $userId, array $filters = [])
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

    return $query->orderByDesc('created_at')->paginate(10);
}

}
