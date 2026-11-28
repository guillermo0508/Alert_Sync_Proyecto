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

    public function test_amex_uses_15_digits_and_4_digit_cvv(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/auth/cards', $this->card(['card_number' => '3782 822463 10005', 'card_cvv' => '1234']))
            ->assertCreated()->assertJsonPath('cards.0.brand', 'AMEX');
        $this->postJson('/api/auth/cards', $this->card(['card_number' => '378282246310005', 'card_cvv' => '123']))
            ->assertStatus(422);
        $this->postJson('/api/auth/cards', $this->card(['card_number' => '3782822463100051', 'card_cvv' => '1234']))
            ->assertStatus(422);
        // Una Visa no acepta CVV de 4.
        $this->postJson('/api/auth/cards', $this->card(['card_cvv' => '1234']))->assertStatus(422);
    }

    public function test_detects_brands(): void
    {
        $this->assertSame('VISA', \App\Models\PaymentCard::detectBrand('4242424242424242'));
        $this->assertSame('MASTERCARD', \App\Models\PaymentCard::detectBrand('2221000000000009'));
        $this->assertSame('MASTERCARD', \App\Models\PaymentCard::detectBrand('5555555555554444'));
        $this->assertSame('AMEX', \App\Models\PaymentCard::detectBrand('371449635398431'));
        $this->assertSame('DISCOVER', \App\Models\PaymentCard::detectBrand('6011111111111117'));
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
