<?php

namespace Tests\Feature;

use App\Mail\ContactVerification;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** El correo de confirmación avisa si no se pudo enviar y llega como correo nuevo en cada reenvío. */
class ContactVerificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function useFailingRelay(): void
    {
        config([
            'mail.default' => 'relay',
            'mail.mailers.relay.endpoint' => 'https://relay.test/api/send',
            'mail.mailers.relay.secret' => 'secreto',
        ]);
        Http::fake(['relay.test/*' => Http::response(['ok' => false, 'error' => 'Invalid login'], 502)]);
    }

    public function test_resend_reports_error_when_email_cannot_be_sent(): void
    {
        $this->useFailingRelay();
        $user = User::factory()->create(['plan' => 'Premium']);
        Sanctum::actingAs($user);
        // Invitación pendiente enviada hace 5 minutos (ya pasó el minuto de espera entre reenvíos).
        $contact = Contact::create([
            'user_id' => $user->id, 'name' => 'Mamá', 'email' => 'mama@example.com', 'verified' => false,
            'verification_token' => 'x', 'verification_token_expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson("/api/contacts/{$contact->id}/resend-verification")
            ->assertStatus(502)
            ->assertJsonPath('mail_sent', false);
    }

    public function test_create_contact_warns_when_email_cannot_be_sent(): void
    {
        $this->useFailingRelay();
        Sanctum::actingAs(User::factory()->create(['plan' => 'Premium']));

        $this->postJson('/api/contacts', ['name' => 'Papá', 'email' => 'papa@example.com'])
            ->assertCreated()
            ->assertJsonPath('mail_sent', false);
    }

    public function test_email_has_unique_subject_and_plain_text_version(): void
    {
        $mail = new ContactVerification('Ana', 'Gus', 'https://web.test/verify-contact/1?token=abc',
            'https://web.test/verify-contact/1?token=abc&action=decline', 15);

        $this->assertMatchesRegularExpression('/\(\d{2}:\d{2} [AP]M\)/', $mail->envelope()->subject);
        $this->assertSame('emails.contact-verification-text', $mail->content()->text);
        $text = view('emails.contact-verification-text', [
            'contactName' => 'Ana', 'ownerName' => 'Gus', 'responseMinutes' => 15,
            'verifyUrl' => 'https://web.test/verify-contact/1?token=abc',
            'declineUrl' => 'https://web.test/verify-contact/1?token=abc&action=decline',
        ])->render();
        $this->assertStringContainsString('https://web.test/verify-contact/1?token=abc', $text);
        $this->assertStringContainsString('No acepto', $text);
    }
}
