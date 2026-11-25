<?php

namespace Tests\Feature;

use App\Mail\ContactVerification;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactInvitationStatusTest extends TestCase
{
    use RefreshDatabase;

    /** Crea un contacto y devuelve [contacto, token, url para aceptar, url para rechazar]. */
    private function invite(): array
    {
        Mail::fake();
        Sanctum::actingAs(User::factory()->create(['plan' => 'Premium']));
        $id = $this->postJson('/api/contacts', ['name' => 'Mamá', 'email' => 'mama@example.com'])
            ->assertCreated()->assertJsonPath('contact.status', 'pending')->json('contact.id');

        $mail = null;
        Mail::assertSent(ContactVerification::class, function ($m) use (&$mail) { $mail = $m; return true; });
        parse_str(parse_url($mail->verifyUrl, PHP_URL_QUERY), $q);

        return [Contact::find($id), $q['token'], $mail];
    }

    public function test_email_mentions_15_minutes_and_has_decline_link(): void
    {
        [, , $mail] = $this->invite();

        $this->assertSame(15, $mail->responseMinutes);
        $this->assertStringContainsString('action=decline', $mail->declineUrl);
        $html = $mail->render();
        $this->assertStringContainsString('15 minutos', $html);
        $this->assertStringContainsString('No acepto', $html);
    }

    public function test_accepting_marks_contact_confirmed(): void
    {
        [$contact, $token] = $this->invite();

        $this->postJson("/api/contacts/{$contact->id}/verify", ['token' => $token])
            ->assertOk()->assertJsonPath('status', 'confirmed');
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'confirmed');
    }

    public function test_declining_marks_contact_rejected_and_link_cannot_be_reused(): void
    {
        [$contact, $token] = $this->invite();

        $this->postJson("/api/contacts/{$contact->id}/decline", ['token' => $token])
            ->assertOk()->assertJsonPath('status', 'rejected');
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'rejected');
        $this->postJson("/api/contacts/{$contact->id}/verify", ['token' => $token])->assertStatus(422);
    }

    public function test_no_answer_in_15_minutes_becomes_rejected(): void
    {
        [$contact, $token] = $this->invite();

        $this->travel(14)->minutes();
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'pending');

        $this->travel(2)->minutes();
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'rejected');
        $this->postJson("/api/contacts/{$contact->id}/verify", ['token' => $token])
            ->assertStatus(422)->assertJsonPath('status', 'rejected');
    }

    public function test_rejected_contact_can_be_invited_again(): void
    {
        [$contact, $token] = $this->invite();
        $this->postJson("/api/contacts/{$contact->id}/decline", ['token' => $token])->assertOk();

        $this->postJson("/api/contacts/{$contact->id}/resend-verification")->assertOk();
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'pending');
        Mail::assertSent(ContactVerification::class, 2);
    }

    public function test_resend_while_pending_has_one_minute_cooldown(): void
    {
        [$contact] = $this->invite();

        $this->postJson("/api/contacts/{$contact->id}/resend-verification")->assertStatus(429);
        $this->travel(61)->seconds();
        $this->postJson("/api/contacts/{$contact->id}/resend-verification")->assertOk();
    }

    public function test_wrong_token_is_rejected(): void
    {
        [$contact] = $this->invite();

        $this->postJson("/api/contacts/{$contact->id}/decline", ['token' => 'nope'])->assertStatus(422);
        $this->getJson('/api/contacts')->assertJsonPath('contacts.0.status', 'pending');
    }

    public function test_contacts_who_declined_do_not_receive_sos(): void
    {
        [$declined, $token] = $this->invite();
        $this->postJson("/api/contacts/{$declined->id}/decline", ['token' => $token])->assertOk();
        $user = $declined->user;
        $ok = Contact::create(['user_id' => $user->id, 'name' => 'Papá', 'email' => 'papa@example.com', 'verified' => true]);

        $notified = $this->postJson('/api/alerts/sos', ['source' => 'web'])->assertSuccessful()
            ->json('alert.contacts_notified');

        $this->assertSame([(string) $ok->id], array_column($notified, 'id'));
    }
}
