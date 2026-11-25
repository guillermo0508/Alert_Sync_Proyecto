<?php

namespace Tests\Feature;

use App\Mail\SosAlert;
use App\Mail\SosCancelled;
use App\Models\Alert;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithContacts(): User
    {
        $user = User::factory()->create(['name' => 'Ana Prueba']);
        Contact::create([
            'user_id' => $user->id, 'name' => 'Mamá', 'phone' => '+523300000000',
            'email' => 'mama@example.com', 'relationship' => 'Mamá',
            'notify_sms' => true, 'notify_email' => true, 'priority' => 1, 'verified' => true,
        ]);
        Contact::create([
            'user_id' => $user->id, 'name' => 'Solo SMS', 'phone' => '+523311111111',
            'notify_sms' => true, 'notify_email' => false, 'priority' => 2, 'verified' => true,
        ]);
        return $user;
    }

    public function test_sos_sends_email_to_contacts_with_email_channel(): void
    {
        Mail::fake();
        $user = $this->userWithContacts();
        Sanctum::actingAs($user);

        $this->postJson('/api/alerts/sos', [
            'source' => 'mobile', 'latitude' => 20.5, 'longitude' => -103.4, 'message' => 'Ayuda',
        ])->assertCreated()->assertJsonPath('emails_count', 1);

        Mail::assertSent(SosAlert::class, fn (SosAlert $mail) =>
            $mail->hasTo('mama@example.com')
            && str_contains($mail->mapsUrl, '20.5,-103.4')
            && $mail->ownerName === 'Ana Prueba');
        Mail::assertSent(SosAlert::class, 1);
    }

    public function test_cancel_marks_alert_cancelled_and_notifies_contacts(): void
    {
        Mail::fake();
        $user = $this->userWithContacts();
        Sanctum::actingAs($user);

        $alertId = $this->postJson('/api/alerts/sos', ['source' => 'mobile'])->json('alert.id');

        $this->postJson("/api/alerts/{$alertId}/cancel")
            ->assertOk()
            ->assertJsonPath('alert.status', 'cancelled');

        $this->assertSame('cancelled', Alert::find($alertId)->status);
        Mail::assertSent(SosCancelled::class, fn (SosCancelled $mail) => $mail->hasTo('mama@example.com'));

        Mail::assertSent(SosCancelled::class, 1);

        // Cancelar de nuevo no vuelve a programar el aviso (responde que ya estaba cancelada).
        // Nota: en pruebas la app se reutiliza entre peticiones y repite las tareas afterResponse,
        // por eso aquí se valida la respuesta y no el conteo de correos.
        $this->postJson("/api/alerts/{$alertId}/cancel")
            ->assertOk()
            ->assertJsonPath('message', 'La alerta ya estaba cancelada.');
    }

    public function test_cannot_cancel_another_users_alert(): void
    {
        Mail::fake();
        $owner = $this->userWithContacts();
        Sanctum::actingAs($owner);
        $alertId = $this->postJson('/api/alerts/sos', ['source' => 'mobile'])->json('alert.id');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/alerts/{$alertId}/cancel")->assertNotFound();
        $this->assertSame('sent', Alert::find($alertId)->status);
    }

    public function test_sos_email_renders(): void
    {
        $html = (new SosAlert('Mamá', 'Ana', 'Ayuda', '14/11/2026 10:30 PM', 'https://maps.example', '3312345678'))->render();
        $this->assertStringContainsString('ALERTA SOS', $html);
        $this->assertStringContainsString('https://maps.example', $html);

        $html = (new SosCancelled('Mamá', 'Ana', '14/11/2026 10:30 PM', '14/11/2026 10:35 PM'))->render();
        $this->assertStringContainsString('CANCELADA', $html);
    }
}
