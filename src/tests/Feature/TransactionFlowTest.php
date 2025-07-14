<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_user()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'João',
            'email' => 'joao@email.com',
            'password' => '123456'
        ]);

        $response->assertCreated()
                 ->assertJsonStructure(['user', 'token']);
    }

    public function test_user_can_deposit()
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/deposit', [
            'value' => 100,
            'description' => 'Depósito inicial'
        ]);

        $response->assertCreated()
                 ->assertJsonFragment(['type' => 'deposit']);
    }

    public function test_user_can_transfer_and_reverse()
    {
        $from = User::factory()->create();
        $to   = User::factory()->create();

        Sanctum::actingAs($from);

        $this->postJson('/api/deposit', ['value' => 200]);

        $transfer = $this->postJson('/api/transfer', [
            'to_user_id' => $to->id,
            'value' => 50,
            'description' => 'Pagamento'
        ]);

        $transfer->assertCreated();
        $transferId = $transfer->json('transfer.id');

        $reversal = $this->postJson('/api/reverse', [
            'transaction_id' => $transferId
        ]);

        $reversal->assertCreated()
                 ->assertJsonFragment(['type' => 'reversal']);
    }

    public function test_user_can_list_transactions()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/deposit', ['value' => 100]);

        $response = $this->getJson('/api/transactions');

        $response->assertOk()
                 ->assertJsonStructure(['data']);
    }

    public function test_user_can_export_csv()
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $this->postJson('/api/deposit', ['value' => 100]);

    $response = $this->get('/api/transactions/export?format=csv');

    $response->assertOk();

    // Alternativa segura para validar o tipo
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
}


    public function test_user_can_export_pdf()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/deposit', ['value' => 100]);

        $response = $this->get('/api/transactions/export?format=pdf');

        $response->assertOk()
                 ->assertHeader('Content-Type', 'application/pdf');
    }
}

