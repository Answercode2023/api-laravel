<?php

namespace App\Repositories;

use App\Models\Balance;
use App\Interfaces\BalanceRepositoryInterface;

class BalanceRepository implements BalanceRepositoryInterface
{
    public function findByUserId(string $userId): ?Balance
    {
        return Balance::find($userId);
    }

    public function create(array $data): Balance
    {
        return Balance::create($data);
    }

    public function updateAmount(string $userId, float $amount): Balance
    {
        $balance = Balance::updateOrCreate(
            ['user_id' => $userId],
            ['amount'  => $amount]
        );

        return $balance;
    }

    public function increment(string $userId, float $amount): Balance
    {
        $balance = $this->findByUserId($userId) ?? $this->create([
            'user_id' => $userId,
            'amount'  => 0,
        ]);

        $balance->amount += $amount;
        $balance->save();

        return $balance;
    }

    public function decrement(string $userId, float $amount): Balance
    {
        return $this->increment($userId, -$amount);
    }
}
