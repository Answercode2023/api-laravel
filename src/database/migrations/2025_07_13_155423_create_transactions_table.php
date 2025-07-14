<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            // Definindo a coluna id como chave primária
            $table->uuid('id')->primary(); // Garantir que 'id' seja chave primária

            // Garantindo índice único para o campo 'id' explicitamente
            $table->uuid('user_id'); // quem fez a transação
            $table->uuid('to_user_id')->nullable(); // quem recebeu (se for o caso)
            $table->enum('type', ['deposit', 'transfer', 'receive', 'reversal']);
            $table->decimal('value', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            // Definindo as chaves estrangeiras
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
