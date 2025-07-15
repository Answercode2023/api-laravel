<?php

namespace App\Interfaces;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;
    public function find(string $id): ?Transaction;
    public function allByUser(string $userId);
    public function findByRelatedId(string $relatedId): ?Transaction;
    public function allByRelatedId(string $relatedId);
    public function listByUser(string $userId, array $filters = []);
    public function getTransactionSummary(string $userId): array;


}
