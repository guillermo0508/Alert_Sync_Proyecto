<?php

namespace Tests\Feature;

use App\Models\PaymentCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Cambiar de plan pagando con una tarjeta guardada (la principal u otra) o con una nueva. */
class PlanChangeWithSavedCardTest extends TestCase
{
    use RefreshDatabase;

    private function card(string $number): array
    {
        return ['card_number' => $number, 'card_expiry' => '08/29', 'card_cvv' => '123', 'card_name' => 'Ana Prueba'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_pays_with_a_saved_card_without_changing_the_main_card(): void
    {
        $user = User::factory()->create(['plan' => 'Básico']);
        Sanctum::actingAs($user);
        $this->postJson('/api/auth/cards', $this->card('4242424242424242'))->assertCreated();
        $other = $this->postJson('/api/auth/cards', $this->card('5555555555554444') + ['make_default' => false])
            ->json('cards');
        $mcId = collect($other)->firstWhere('last_four', '4444')['id'];

        $this->postJson('/api/plans/change', ['plan_id' => 'premium', 'card_id' => $mcId])
            ->assertOk()
            ->assertJsonPath('user.plan', 'Premium');

        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'plan' => 'Premium', 'card_last_four' => '4444']);
        // La tarjeta principal (cobro automático) sigue siendo la misma.
        $this->assertSame('4242', $user->fresh()->payment_card_last_four);
    }

    public function test_new_card_is_saved_and_becomes_main_card_by_default(): void
    {
        $user = User::factory()->create(['plan' => 'Premium']);
        Sanctum::actingAs($user);

        $this->postJson('/api/plans/change', ['plan_id' => 'basico'] + $this->card('4242424242424242'))
            ->assertOk()->assertJsonPath('user.plan', 'Básico');

        $this->assertSame(1, PaymentCard::where('user_id', $user->id)->where('is_default', true)->count());
        $this->assertSame('4242', $user->fresh()->payment_card_last_four);
    }

    public function test_new_card_can_be_saved_without_becoming_main_card(): void
    {
        $user = User::factory()->create(['plan' => 'Básico']);
        Sanctum::actingAs($user);
        $this->postJson('/api/auth/cards', $this->card('4242424242424242'))->assertCreated();

        $this->postJson('/api/plans/change', ['plan_id' => 'premium', 'make_default' => false] + $this->card('5555555555554444'))
            ->assertOk();

        $this->assertSame(2, PaymentCard::where('user_id', $user->id)->count());
        $this->assertSame('4242', $user->fresh()->payment_card_last_four);
    }

    public function test_cannot_pay_with_another_users_card(): void
    {
        $owner = User::factory()->create();
        $card = PaymentCard::create(['user_id' => $owner->id, 'last_four' => '1111', 'card_name' => 'X',
            'card_expiry' => '01/30', 'is_default' => true]);
        Sanctum::actingAs(User::factory()->create(['plan' => 'Básico']));

        $this->postJson('/api/plans/change', ['plan_id' => 'premium', 'card_id' => $card->id])
            ->assertStatus(422);
    }

    public function test_receipt_date_uses_mexico_time(): void
    {
        // 27/11 a las 7:14 p.m. en México = 28/11 01:14 UTC. El recibo debe decir 27/12, no 28/12.
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-28 01:14:00', 'UTC'));
        Sanctum::actingAs(User::factory()->create(['plan' => 'Básico']));

        $this->postJson('/api/plans/change', ['plan_id' => 'premium'] + $this->card('4242424242424242'))->assertOk();

        Mail::assertSent(\App\Mail\PaymentReceipt::class, function ($mail) {
            return str_contains(json_encode(get_object_vars($mail)), '27\/12\/2026');
        });
        \Illuminate\Support\Carbon::setTestNow();
    }
}
