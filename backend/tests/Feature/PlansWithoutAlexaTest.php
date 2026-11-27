<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Alexa ya no aparece en los planes ni en la página, y la web en Vercel puede cargar los planes. */
class PlansWithoutAlexaTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_alexa_from_saved_plans_and_site_content(): void
    {
        DB::table('plans')->insert([
            'plan_id' => 'premium', 'name' => 'Premium', 'price_amount' => 200, 'max_contacts' => 15,
            'features' => json_encode(['Hasta 15 contactos de emergencia', 'Soporte para Smart Watch y Alexa']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('site_contents')->insert([
            'hero_subtitle' => 'ALERTSYNC vincula tu smartwatch y tu Alexa para enviar alertas.',
            'features' => json_encode([
                ['icon' => '⌚', 'title' => 'Smartwatch', 'text' => 'SOS desde el reloj.'],
                ['icon' => '🗣️', 'title' => 'Control por Voz con Alexa', 'text' => 'Di "Alexa, pedir ayuda".'],
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        (require database_path('migrations/2026_11_26_000001_update_plans_without_alexa.php'))->up();

        $plans = $this->getJson('/api/plans')->assertOk()->json('plans');
        $this->assertStringNotContainsStringIgnoringCase('alexa', json_encode($plans, JSON_UNESCAPED_UNICODE));
        $premium = collect($plans)->firstWhere('id', 'premium');
        $this->assertContains('Alerta automática por pulso anormal', $premium['features']);

        $content = $this->getJson('/api/site-content')->assertOk()->json('content');
        $this->assertStringNotContainsStringIgnoringCase('alexa', json_encode($content, JSON_UNESCAPED_UNICODE));
        $this->assertSame('Smartwatch', $content['features'][0]['title']);
    }

    public function test_vercel_web_page_is_allowed_by_cors(): void
    {
        $this->withHeaders(['Origin' => 'https://alertsync-frontend.vercel.app'])
            ->getJson('/api/plans')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://alertsync-frontend.vercel.app');

        $this->withHeaders(['Origin' => 'https://otro-sitio.vercel.app'])
            ->getJson('/api/plans')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
