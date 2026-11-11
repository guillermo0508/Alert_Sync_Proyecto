<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostgresApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_relational_records_and_multiple_device_tokens_are_saved(): void
    {
        SiteContent::create([
            'hero_title' => 'Contenido de prueba',
            'features' => [['title' => 'Característica']],
            'faqs' => [['q' => 'Pregunta', 'a' => 'Respuesta']],
        ]);

        $user = User::factory()->create([
            'devices' => ['watch' => true, 'alexa' => false],
            'device_names' => ['watch' => 'ALERTSYNC Watch', 'alexa' => null],
        ]);

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => 'Contacto de emergencia',
            'phone' => '+521234567890',
            'priority' => 1,
        ]);

        $alert = Alert::create([
            'user_id' => $user->id,
            'source' => 'watch',
            'status' => 'sent',
            'contacts_notified' => [['id' => $contact->id, 'name' => $contact->name]],
            'metadata' => ['device' => 'watch'],
        ]);

        $user->createToken('alertsync-mobile');
        $user->createToken('alertsync-watch');

        $this->assertSame($user->id, $contact->fresh()->user_id);
        $this->assertSame([['id' => $contact->id, 'name' => $contact->name]], $alert->fresh()->contacts_notified);
        $this->assertSame(['device' => 'watch'], $alert->fresh()->metadata);
        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->getJson('/api/site-content')->assertOk()
            ->assertJsonPath('content.hero_title', 'Contenido de prueba');
        $this->getJson('/api/plans')->assertOk()->assertJsonCount(2, 'plans');
    }

    public function test_smartwatch_authentication_and_sos_work_with_sql_storage(): void
    {
        $user = User::factory()->create([
            'watch_access_code' => '123456',
            'watch_access_code_expires_at' => now()->addMinutes(10),
            'devices' => ['watch' => false, 'alexa' => false],
        ]);

        $mobileLogin = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->withToken($mobileLogin->json('token'))
            ->postJson('/api/alerts/sos', [
                'source' => 'mobile',
                'latitude' => 19.432608,
                'longitude' => -99.133209,
            ])
            ->assertCreated()
            ->assertJsonPath('alert.source', 'mobile');

        $login = $this->postJson('/api/auth/watch-login', ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('user.id', (string) $user->id);

        $this->withToken($login->json('token'))
            ->postJson('/api/alerts/sos', [
                'source' => 'watch',
                'latitude' => 19.432608,
                'longitude' => -99.133209,
            ])
            ->assertCreated()
            ->assertJsonPath('alert.user_id', $user->id);

        $this->assertDatabaseCount('alerts', 2);
        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertTrue($user->fresh()->devices['watch']);
    }

    public function test_admin_web_pages_can_read_and_update_postgres_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['plan' => 'Premium']);
        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => 'Contacto de emergencia',
            'priority' => 1,
        ]);
        $alert = Alert::create([
            'user_id' => $user->id,
            'source' => 'watch',
            'status' => 'sent',
            'message' => 'Prueba de PostgreSQL',
            'contacts_notified' => [['id' => $contact->id]],
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'plan' => 'Premium',
            'amount' => 200,
            'currency' => 'MXN',
            'status' => 'completed',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.total_users', 2)
            ->assertJsonPath('recent_alerts.0.id', $alert->id)
            ->assertJsonPath('recent_payments.0.id', $payment->id);

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $user->id]);

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('contacts.0.id', $contact->id)
            ->assertJsonPath('alerts.0.id', $alert->id)
            ->assertJsonPath('payments.0.id', $payment->id);

        $this->getJson('/api/admin/alerts')
            ->assertOk()
            ->assertJsonPath('alerts.0.id', (string) $alert->id);

        $this->getJson('/api/admin/payments')
            ->assertOk()
            ->assertJsonPath('payments.0.id', (string) $payment->id)
            ->assertJsonPath('payments.0.user_name', $user->name);

        $this->putJson('/api/admin/site-content', [
            'hero_badge' => 'Sitio actualizado',
            'hero_title' => 'Contenido PostgreSQL',
            'hero_subtitle' => 'Contenido guardado desde administración.',
            'features' => [
                ['icon' => 'A', 'title' => 'Una', 'text' => 'Primera'],
                ['icon' => 'B', 'title' => 'Dos', 'text' => 'Segunda'],
                ['icon' => 'C', 'title' => 'Tres', 'text' => 'Tercera'],
            ],
            'faqs' => [['q' => 'Pregunta', 'a' => 'Respuesta']],
            'cta_title' => 'Comenzar',
            'cta_subtitle' => 'Crear cuenta',
        ])->assertOk();

        $this->putJson('/api/admin/plans/basico', [
            'name' => 'Plan Básico',
            'price_amount' => 125,
            'features' => ['Contactos', 'Alertas'],
            'max_contacts' => 5,
        ])->assertOk();

        $this->postJson("/api/admin/payments/{$payment->id}/refund")
            ->assertOk();

        $this->deleteJson("/api/admin/users/{$user->id}/contacts/{$contact->id}")
            ->assertOk();

        $this->assertDatabaseHas('site_contents', ['hero_title' => 'Contenido PostgreSQL']);
        $this->assertDatabaseHas('plans', ['plan_id' => 'basico', 'price_amount' => 125]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
