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
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('to_user_id')->nullable();
            $table->enum('type', ['deposit', 'transfer', 'receive', 'reversal']);
            $table->decimal('value', 15, 2);
            $table->string('description')->nullable();
            $table->uuid('related_id')->nullable(); // Adiciona a coluna, mas ainda sem foreign
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('set null');
            // NÃO adiciona a foreign ainda para related_id
        });

        // Agora adiciona a foreign para related_id separadamente
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('related_id')->references('id')->on('transactions')->onDelete('set null');
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
