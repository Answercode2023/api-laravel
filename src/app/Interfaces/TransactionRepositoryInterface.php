<?php

namespace App\Interfaces;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;
    public function find(string $id): ?Transaction;
    public function allByUser(string $userId);
    public function findByRelatedId(string $relatedId): ?Transaction;
}
