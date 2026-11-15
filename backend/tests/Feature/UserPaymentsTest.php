<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_payments_newest_first(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Payment::create(['user_id' => $me->id, 'plan' => 'Básico', 'amount' => 100, 'currency' => 'MXN',
                         'card_last_four' => '4242', 'status' => 'completed', 'created_at' => now()->subMonth()]);
        Payment::create(['user_id' => $me->id, 'plan' => 'Premium', 'amount' => 200, 'currency' => 'MXN',
                         'card_last_four' => '4242', 'status' => 'completed']);
        Payment::create(['user_id' => $other->id, 'plan' => 'Básico', 'amount' => 100, 'currency' => 'MXN',
                         'status' => 'completed']);

        Sanctum::actingAs($me);
        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonCount(2, 'payments')
            ->assertJsonPath('payments.0.plan', 'Premium')
            ->assertJsonMissingPath('payments.0.card_name');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/payments')->assertUnauthorized();
    }
}
