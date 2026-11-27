<?php

use App\Models\Plan;
use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alexa se retiró del producto. Actualiza los planes y el contenido de la página que ya están
 * guardados en la base de datos:
 *  - Básico: signos vitales básicos en tiempo real (pulso, pasos y batería).
 *  - Premium: todos los signos vitales en tiempo real y alerta automática por pulso anormal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans')) {
            foreach (Plan::defaults() as $planId => $plan) {
                DB::table('plans')->where('plan_id', $planId)->update([
                    'features' => json_encode($plan['features'], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('site_contents')) {
            $defaults = SiteContent::defaults();
            $vitals = $defaults['features'][1];

            foreach (DB::table('site_contents')->get() as $row) {
                $changes = [];
                if (stripos((string) $row->hero_subtitle, 'alexa') !== false) {
                    $changes['hero_subtitle'] = $defaults['hero_subtitle'];
                }
                $features = json_decode((string) $row->features, true);
                if (is_array($features)) {
                    $updated = array_map(
                        fn ($f) => stripos(($f['title'] ?? '').' '.($f['text'] ?? ''), 'alexa') !== false ? $vitals : $f,
                        $features
                    );
                    if ($updated !== $features) {
                        $changes['features'] = json_encode($updated, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($changes) {
                    DB::table('site_contents')->where('id', $row->id)->update($changes + ['updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        // Sin reversa: Alexa ya no forma parte del producto.
    }
};
