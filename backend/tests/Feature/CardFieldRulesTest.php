<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Tarjeta: 16 dígitos, CVV de 3 y vencimiento MM/AA válido y vigente. */
class CardFieldRulesTest extends TestCase
{
    use RefreshDatabase;

    private function card(array $overrides = []): array
    {
        return array_merge(['card_number' => '4242424242424242', 'card_expiry' => '08/29',
            'card_cvv' => '123', 'card_name' => 'Ana Prueba'], $overrides);
    }

    public function test_valid_card_with_spaces_is_accepted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/auth/cards', $this->card(['card_number' => '4242 4242 4242 4242']))->assertCreated();
    }

    public function test_invalid_card_fields_are_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $cases = [
            ['card_number' => '42424242424242424'],   // 17 dígitos
            ['card_number' => '424242424242424'],     // 15 dígitos
            ['card_cvv' => '1234'],
            ['card_cvv' => '12'],
            ['card_expiry' => '13/29'],
            ['card_expiry' => '00/29'],
            ['card_expiry' => '0829'],
            ['card_expiry' => '01/20'],               // vencida
        ];
        foreach ($cases as $bad) {
            $this->postJson('/api/auth/cards', $this->card($bad))->assertStatus(422);
        }
    }
}
