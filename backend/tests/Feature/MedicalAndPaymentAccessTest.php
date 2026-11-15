<?php

namespace Tests\Feature;

use App\Mail\PaymentAccessCode;
use App\Mail\SosAlert;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicalAndPaymentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_profile_is_saved_and_returned(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/medical', ['blood_type' => 'O+', 'allergies' => 'Penicilina'])
            ->assertOk()
            ->assertJsonPath('user.blood_type', 'O+')
            ->assertJsonPath('user.allergies', 'Penicilina');

        $this->putJson('/api/auth/medical', ['blood_type' => 'Z+'])->assertUnprocessable();
    }

    public function test_sos_email_includes_medical_profile(): void
    {
        Mail::fake();
        $user = User::factory()->create(['blood_type' => 'AB-', 'allergies' => 'Nueces']);
        Contact::create(['user_id' => $user->id, 'name' => 'Mamá', 'email' => 'mama@example.com',
                         'notify_email' => true, 'priority' => 1]);
        Sanctum::actingAs($user);

        $this->postJson('/api/alerts/sos', ['source' => 'mobile'])->assertCreated();

        Mail::assertSent(SosAlert::class, fn (SosAlert $m) => $m->bloodType === 'AB-' && $m->allergies === 'Nueces');
        $html = (new SosAlert('Mamá', 'Ana', 'Ayuda', 'hoy', null, null, 'AB-', 'Nueces'))->render();
        $this->assertStringContainsString('FICHA MÉDICA', $html);
        $this->assertStringContainsString('AB-', $html);
    }

    public function test_payment_access_code_flow(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'guille@example.com']);
        Sanctum::actingAs($user);

        $this->postJson('/api/auth/payment-access/send')->assertOk()->assertJsonPath('email', 'gu••••@example.com');
        $code = null;
        Mail::assertSent(PaymentAccessCode::class, function (PaymentAccessCode $m) use (&$code) {
            $code = $m->code;
            return $m->hasTo('guille@example.com');
        });

        // Reenvío inmediato bloqueado
        $this->postJson('/api/auth/payment-access/send')->assertStatus(429);

        $this->postJson('/api/auth/payment-access/verify', ['code' => '000000'])->assertUnprocessable();
        $this->postJson('/api/auth/payment-access/verify', ['code' => $code])->assertOk()->assertJsonPath('verified', true);
        // Un solo uso
        $this->postJson('/api/auth/payment-access/verify', ['code' => $code])->assertUnprocessable();

        $this->assertStringContainsString($code, (new PaymentAccessCode('Guille', $code, 10))->render());
    }
}
