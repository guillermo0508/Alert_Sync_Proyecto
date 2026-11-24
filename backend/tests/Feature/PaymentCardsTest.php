<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentCardsTest extends TestCase
{
    use RefreshDatabase;

    private function card(string $number, string $name = 'Ana Prueba'): array
    {
        return ['card_number' => $number, 'card_expiry' => '08/29', 'card_cvv' => '123', 'card_name' => $name];
    }

    public function test_add_switch_and_delete_cards(): void
    {
        $user = User::factory()->create(['auto_renew' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/cards', $this->card('4242424242424242'))
            ->assertCreated()
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.brand', 'VISA')
            ->assertJsonPath('cards.0.is_default', true);

        // Segunda tarjeta sin hacerla predeterminada
        $res = $this->postJson('/api/auth/cards', $this->card('5555555555554444') + ['make_default' => false])
            ->assertCreated()->assertJsonCount(2, 'cards');
        $this->assertSame('4242', $user->fresh()->payment_card_last_four);

        $mcId = collect($res->json('cards'))->firstWhere('last_four', '4444')['id'];
        $this->postJson("/api/auth/cards/{$mcId}/default")->assertOk()
            ->assertJsonPath('cards.0.last_four', '4444');
        $fresh = $user->fresh();
        $this->assertSame('4444', $fresh->payment_card_last_four);
        $this->assertFalse((bool) $fresh->auto_renew, 'Cambiar tarjeta no debe reactivar la suscripción');

        // Borrar la predeterminada promueve la otra
        $this->deleteJson("/api/auth/cards/{$mcId}")->assertOk()->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.is_default', true);
        $this->assertSame('4242', $user->fresh()->payment_card_last_four);
    }

    public function test_legacy_card_is_imported_and_cards_are_private(): void
    {
        $user = User::factory()->create(['payment_card_last_four' => '1111', 'payment_card_name' => 'Ana',
                                         'payment_card_expiry' => '01/30']);
        Sanctum::actingAs($user);
        $id = $this->getJson('/api/auth/cards')->assertOk()->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.last_four', '1111')->json('cards.0.id');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/auth/cards/{$id}/default")->assertNotFound();
        $this->deleteJson("/api/auth/cards/{$id}")->assertNotFound();
    }
}
