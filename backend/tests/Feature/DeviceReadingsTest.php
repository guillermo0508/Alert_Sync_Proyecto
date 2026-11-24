<?php

namespace Tests\Feature;

use App\Models\DeviceReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_watch_reading_is_stored_and_shown_as_latest(): void
    {
        $me = User::factory()->create(['plan' => 'Básico']);
        Sanctum::actingAs($me);

        $this->postJson('/api/readings', ['device' => 'watch', 'heart_rate' => 72, 'spo2' => 98, 'steps' => 4200, 'battery' => 80])
            ->assertCreated();
        $this->postJson('/api/readings', ['device' => 'mobile', 'latitude' => 20.6736, 'longitude' => -103.344, 'accuracy' => 12])
            ->assertCreated();

        $this->getJson('/api/readings/latest')
            ->assertOk()
            ->assertJsonPath('vitals.heart_rate.value', 72)
            ->assertJsonPath('vitals.heart_rate.device', 'watch')
            ->assertJsonPath('vitals.spo2.value', 98)
            ->assertJsonPath('location.lat', 20.6736)
            ->assertJsonPath('location.device', 'mobile')
            ->assertJsonPath('devices.watch.online', true)
            ->assertJsonPath('devices.watch.battery', 80)
            ->assertJsonPath('devices.mobile.online', true);
    }

    public function test_rejects_empty_or_invalid_readings(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/readings', ['device' => 'watch'])->assertStatus(422);
        $this->postJson('/api/readings', ['device' => 'tv', 'heart_rate' => 70])->assertStatus(422);
        $this->postJson('/api/readings', ['device' => 'watch', 'heart_rate' => 900])->assertStatus(422);
        $this->postJson('/api/readings', ['device' => 'mobile', 'latitude' => 20.1])->assertStatus(422);
    }

    public function test_users_only_see_their_own_readings(): void
    {
        $other = User::factory()->create();
        DeviceReading::create(['user_id' => $other->id, 'device' => 'watch', 'heart_rate' => 110, 'recorded_at' => now()]);

        Sanctum::actingAs(User::factory()->create(['plan' => 'Premium']));
        $this->getJson('/api/readings/latest')->assertOk()->assertJsonPath('vitals.heart_rate', null)
            ->assertJsonPath('devices.watch.online', false);
        $this->getJson('/api/readings')->assertOk()->assertJsonCount(0, 'readings');
    }

    public function test_history_is_premium_only_and_filtered_by_range(): void
    {
        $premium = User::factory()->create(['plan' => 'Premium']);
        DeviceReading::create(['user_id' => $premium->id, 'device' => 'watch', 'heart_rate' => 60, 'recorded_at' => now()->subHours(30)]);
        DeviceReading::create(['user_id' => $premium->id, 'device' => 'watch', 'heart_rate' => 70, 'recorded_at' => now()->subHours(2)]);
        DeviceReading::create(['user_id' => $premium->id, 'device' => 'watch', 'heart_rate' => 80, 'recorded_at' => now()->subMinutes(5)]);

        Sanctum::actingAs($premium);
        $this->getJson('/api/readings?range=24h')->assertOk()->assertJsonPath('locked', false)
            ->assertJsonCount(2, 'readings')->assertJsonPath('readings.0.heart_rate', 70);
        $this->getJson('/api/readings?range=7d')->assertOk()->assertJsonCount(3, 'readings');

        Sanctum::actingAs(User::factory()->create(['plan' => 'Básico']));
        $this->getJson('/api/readings')->assertOk()->assertJsonPath('locked', true)->assertJsonCount(0, 'readings');
    }

    public function test_old_readings_are_pruned_and_future_dates_clamped(): void
    {
        $me = User::factory()->create();
        DeviceReading::create(['user_id' => $me->id, 'device' => 'watch', 'heart_rate' => 60, 'recorded_at' => now()->subDays(40)]);

        Sanctum::actingAs($me);
        $this->postJson('/api/readings', ['device' => 'watch', 'heart_rate' => 75, 'recorded_at' => now()->addDay()->toIso8601String()])
            ->assertCreated();

        $this->assertSame(1, DeviceReading::where('user_id', $me->id)->count());
        $this->assertTrue(DeviceReading::first()->recorded_at->lte(now()));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/readings/latest')->assertUnauthorized();
        $this->postJson('/api/readings', ['device' => 'watch', 'heart_rate' => 70])->assertUnauthorized();
    }
}
