<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionService;
// use PDF;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use  App\Models\User;
use Illuminate\Validation\ValidationException; // Importe esta classe
use Illuminate\Http\JsonResponse;


class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }


    /**
     * Realiza um depósito na conta do usuário autenticado.
     * Requer valor positivo e opcionalmente uma descrição.
     */
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



    /**
     * Realiza uma transferência entre usuários.
     * Cria duas transações: transfer (origem) e receive (destino).
     */
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


    /**
     * Reverte uma transação existente do usuário.
     * Apenas transações válidas podem ser revertidas.
     */
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
    /**
     * Lista as transações do usuário autenticado com filtros opcionais.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'data_inicial', 'data_final']);

        $transactions = $this->transactionService->list(
            $request->user()->id,
            $filters
        );

        return response()->json($transactions);
    }



/**
     * Exporta as transações do usuário autenticado em PDF ou CSV.
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv'); // default CSV
        $filters = $request->only(['type', 'data_inicial', 'data_final']);
        $transactions = $this->transactionService->list(
            $request->user()->id,
            $filters,
            1000 // pega até 1000 registros para exportação
        );

        if ($format === 'pdf') {
            $pdf = PDF::loadView('exports.transactions', ['transactions' => $transactions]);
            return $pdf->download('extrato.pdf');
        }

        if ($format === 'csv') {
            $csvContent = implode(",", ['ID', 'Tipo', 'Valor', 'Descrição', 'Data']) . "\n";

            foreach ($transactions as $t) {
                $csvContent .= implode(",", [
                    $t->id,
                    $t->type,
                    number_format($t->value, 2, '.', ''),
                    '"' . str_replace('"', '""', $t->description) . '"',
                    $t->created_at->format('Y-m-d H:i:s')
                ]) . "\n";
            }

            return Response::make($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="extrato.csv"',
            ]);
        }

        return response()->json(['error' => 'Formato inválido'], 400);
    }


    /**
     * Lista as transações de um usuário específico por ID.
     * Gera nome do remetente e destinatário de forma interpretada.
     */
    public function getUserTransactions(string $userId)
    {
        try {
            // Verifica se o usuário existe para evitar erros desnecessários no service
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            $transactions = $this->transactionService->getUserTransactions($userId);

            $formattedTransactions = $transactions->map(function ($transaction) use ($userId, $user) {
                // nome_usuario_origem DEVE ser sempre o nome do user_id da transação
                $ownerName = optional($transaction->user)->name;

                $receiverName = null;

                // Lógica para 'nome_quem_recebeu'
                if ($transaction->type === 'transfer') {
                    // Se a transação é uma 'transfer', quem recebeu é 'toUser'
                    $receiverName = optional($transaction->toUser)->name;
                } elseif ($transaction->type === 'receive') {
                    // Se a transação é um 'receive', quem recebeu é o 'user' da transação
                    // E quem enviou é o 'toUser' da transação, ou o 'user' da transação relacionada
                    $receiverName = optional($transaction->user)->name; // O usuário da transação 'receive' é quem recebeu
                    if ($transaction->related && $transaction->related->user) {
                        $receiverName = optional($transaction->related->user)->name; // Quem enviou originalmente
                    } else {
                        // Se não tem related_id ou o related_id não tem user (o que é improvável)
                        // ou se o 'toUser' da transação 'receive' for diferente do 'user' da transação 'receive'
                        $receiverName = optional($transaction->toUser)->name;
                    }
                } elseif ($transaction->type === 'deposit' || $transaction->type === 'reversal') {
                    // Para depósito e estorno, geralmente o destinatário é o próprio usuário
                    $receiverName = optional($transaction->user)->name;
                    if ($receiverName === $user->name && $transaction->type === 'reversal') {
                        // Se o estorno foi feito pelo próprio usuário, o recebedor é ele mesmo
                        $receiverName = 'Mesmo usuário';
                    }
                }

                // Casos especiais para 'nome_quem_recebeu' se for o mesmo usuário
                if ($transaction->to_user_id === $transaction->user_id || ($transaction->type !== 'transfer' && $transaction->type !== 'receive')) {
                    $receiverName = 'Mesmo usuário';
                }

                return [
                    'id'                 => $transaction->id,
                    'nome_usuario_origem' => $ownerName, // Sempre o nome do user_id da transação
                    'valor_transferido'  => (float) $transaction->value,
                    'tipo_transferencia' => $transaction->type,
                    'nome_quem_recebeu'  => $receiverName,
                    'data_transferencia' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'user_id' => $userId,
                'transactions' => $formattedTransactions,
            ], 200);
        } catch (\Exception $e) {
            // Log::error('Erro ao buscar transações por ID de usuário: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Ocorreu um erro interno ao buscar as transações.'], 500);
        }
    }


    /**
     * Retorna o saldo e estatísticas consolidadas do extrato do usuário.
     */
    public function getUserStatement(string $userId): JsonResponse
    {
        try {
            // Garante que o usuário autenticado está requisitando seu próprio extrato
            // Ou, se for uma rota de admin, pode ser removido
            if (auth()->user()->id !== $userId) {
                return response()->json([
                    'message' => 'Não autorizado a acessar o extrato deste usuário.',
                ], 403);
            }

            $statement = $this->transactionService->getUserStatement($userId);

            return response()->json($statement, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocorreu um erro ao buscar o extrato do usuário.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
