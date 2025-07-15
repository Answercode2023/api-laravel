<?php

namespace App\Services;

use App\Interfaces\BalanceRepositoryInterface;
use App\Interfaces\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use  App\Models\User;


class TransactionService
{
    protected $balanceRepo;
    protected $transactionRepo;

    public function __construct(
        BalanceRepositoryInterface $balanceRepo,
        TransactionRepositoryInterface $transactionRepo
    ) {
        $this->balanceRepo     = $balanceRepo;
        $this->transactionRepo = $transactionRepo;
    }


    /**
     * Realiza um depósito no saldo do usuário informado.
     * Cria uma transação do tipo "deposit" e incrementa o saldo.
     */
    public function deposit(string $userId, float $value, string $description = null)
    {
        return DB::transaction(function () use ($userId, $value, $description) {
            $transaction = $this->transactionRepo->create([
                'user_id'    => $userId,
                'type'       => 'deposit',
                'value'      => $value,
                'description' => $description,
            ]);

            $this->balanceRepo->increment($userId, $value);

            return $transaction;
        });
    }


    /**
     * Realiza uma transferência entre dois usuários.
     * Cria duas transações: transfer (origem) e receive (destino).
     * Ambos os saldos são ajustados no final da operação.
     */
    public function transfer(string $fromUserId, string $toUserId, float $value, string $description = null)
    {
        return DB::transaction(function () use ($fromUserId, $toUserId, $value, $description) {
            $balance = $this->balanceRepo->findByUserId($fromUserId);

            if (!$balance || $balance->amount < $value) {
                throw ValidationException::withMessages([
                    'saldo' => ['Saldo insuficiente para a transferência.'],
                ]);
            }

            $transfer = $this->transactionRepo->create([
                'user_id'    => $fromUserId,
                'to_user_id' => $toUserId,
                'type'       => 'transfer',
                'value'      => $value,
                'description' => $description ?? "Transferência para $toUserId",
            ]);




            $receive = $this->transactionRepo->create([
                'user_id'    => $toUserId,
                'to_user_id' => $fromUserId,
                'type'       => 'receive',
                'value'      => $value,
                'description' => $description ?? "Recebido de $fromUserId",
                'related_id' => $transfer->id, // Agora o ID do transfer já existe
            ]);


            $this->balanceRepo->decrement($fromUserId, $value);
            $this->balanceRepo->increment($toUserId, $value);

            return [$transfer, $receive];
        });
    }


    /**
     * Reverte uma transação caso ainda não tenha sido revertida anteriormente.
     * A reversão ajusta os saldos envolvidos de acordo com o tipo original.
     */
    public function reverse(string $transactionId, string $requestedByUserId)
    {
        return DB::transaction(function () use ($transactionId, $requestedByUserId) {
            $original = $this->transactionRepo->find($transactionId);

            $reversals = $this->transactionRepo->allByRelatedId($original->id);

            $alreadyReversed = $reversals->firstWhere('type', 'reversal');

            if ($alreadyReversed) {
                // throw ValidationException::withMessages([
                //     'transacao' => ['Essa transação já foi revertida anteriormente.'],
                // ]);

                return (object) [
                    'message' => 'Essa transação já foi revertida anteriormente.',
                ];
            }


            if (!$original) {
                throw ValidationException::withMessages([
                    'transacao' => ['Transação não encontrada.'],
                ]);
            }

            // Evita múltiplas reversões
            if ($original->type === 'reversal') {
                throw ValidationException::withMessages([
                    'transacao' => ['Transação já foi revertida.'],
                ]);
            }

            // Reverter o saldo
            switch ($original->type) {
                case 'deposit':
                    $this->balanceRepo->decrement($original->user_id, $original->value);
                    break;

                case 'transfer':
                    // 1. Quem enviou recupera o valor
                    $this->balanceRepo->increment($original->user_id, $original->value);

                    // 2. Quem recebeu perde (busca o receive que possui related_id = id da transfer)
                    $related = $this->transactionRepo->findByRelatedId($original->id);   // ← aqui é $original->id

                    if ($related) {
                        $this->balanceRepo->decrement($related->user_id, $original->value);
                    }
                    break;


                case 'receive':
                    $this->balanceRepo->decrement($original->user_id, $original->value);
                    break;
            }

            // Cria a reversão
            return $this->transactionRepo->create([
                'user_id'    => $requestedByUserId,
                'type'       => 'reversal',
                'value'      => $original->value,
                'description' => 'Reversão da transação ' . $original->id,
                'related_id' => $original->id,
            ]);
        });
    }


    /**
     * Lista as transações do usuário com filtros opcionais (tipo e datas).
     * Suporta paginação via limite (padrão: 10).
     */
    public function list(string $userId, array $filters = [], int $limit = 10)
    {
        return $this->transactionRepo->listByUser($userId, $filters, $limit);
    }


    /**
     * Lista todas as transações associadas ao usuário,
     * tanto enviadas quanto recebidas.
     */
    public function getUserTransactions(string $userId)
    {

        return $this->transactionRepo->allByUser($userId);
    }

     /**
     * Retorna uma transação específica pelo ID.
     * Usado em fluxos que precisam de detalhes extras.
     */
    public function find(string $transactionId)
    {
        return $this->transactionRepo->find($transactionId);
    }


     /**
     * Retorna um resumo financeiro do usuário:
     * saldo atual, total depositado, transferido, recebido e estornado.
     */
    public function getUserStatement(string $userId): array
    {
        // Verifica se o usuário existe para tratamento de erro adequado
        $userExists = User::where('id', $userId)->exists();
        if (!$userExists) {
            throw ValidationException::withMessages([
                'user_id' => ['Usuário não encontrado.'],
            ]);
        }

        return $this->transactionRepo->getTransactionSummary($userId);
    }
}
