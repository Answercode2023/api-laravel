<?php

namespace App\Interfaces;

use App\Models\Balance;

interface BalanceRepositoryInterface
{
    public function findByUserId(string $userId): ?Balance;
    public function create(array $data): Balance;
    public function updateAmount(string $userId, float $amount): Balance;
    public function increment(string $userId, float $amount): Balance;
    public function decrement(string $userId, float $amount): Balance;
}
