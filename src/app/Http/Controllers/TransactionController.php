<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionService;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'value'       => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $transaction = $this->transactionService->deposit(
            $request->user()->id,
            $data['value'],
            $data['description'] ?? null
        );

        return response()->json($transaction, 201);
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'to_user_id'  => 'required|uuid|exists:users,id',
            'value'       => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $result = $this->transactionService->transfer(
            $request->user()->id,
            $data['to_user_id'],
            $data['value'],
            $data['description'] ?? null
        );

        return response()->json(['transfer' => $result[0], 'receive' => $result[1]], 201);
    }

    public function reverse(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|uuid|exists:transactions,id',
        ]);

        $reversal = $this->transactionService->reverse(
            $data['transaction_id'],
            $request->user()->id
        );

        return response()->json($reversal, 201);
    }

    public function index(Request $request)
{
    $filters = $request->only(['type', 'data_inicial', 'data_final']);

    $transactions = $this->transactionService->list(
        $request->user()->id,
        $filters
    );

    return response()->json($transactions);
}

}
