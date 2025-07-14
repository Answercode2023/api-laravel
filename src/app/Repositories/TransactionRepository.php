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
}
