<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertHistoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function alertFor(User $user, string $source = 'watch'): Alert
    {
        return Alert::create(['user_id' => $user->id, 'source' => $source, 'status' => 'sent', 'message' => 'SOS']);
    }

    public function test_deleting_one_alert_hides_it_for_every_device(): void
    {
        $me = User::factory()->create();
        $a = $this->alertFor($me);
        $b = $this->alertFor($me, 'mobile');
        Sanctum::actingAs($me);

        $this->deleteJson("/api/alerts/{$a->id}")->assertOk()->assertJsonPath('deleted', 1);

        $this->getJson('/api/alerts')->assertOk()
            ->assertJsonCount(1, 'alerts')
            ->assertJsonPath('alerts.0.id', $b->id)
            ->assertJsonPath('deleted_count', 1)
            ->assertJsonMissingPath('alerts.0.user_deleted_at');
        $this->assertNotNull($a->fresh()->user_deleted_at, 'El registro se conserva para administración');
    }

    public function test_clear_all_and_clear_selected_ids(): void
    {
        $me = User::factory()->create();
        [$a, $b, $c] = [$this->alertFor($me), $this->alertFor($me), $this->alertFor($me)];
        Sanctum::actingAs($me);

        $this->postJson('/api/alerts/clear', ['ids' => [$a->id, $b->id]])->assertOk()->assertJsonPath('deleted', 2);
        $this->getJson('/api/alerts')->assertJsonCount(1, 'alerts')->assertJsonPath('alerts.0.id', $c->id);

        $this->postJson('/api/alerts/clear')->assertOk()->assertJsonPath('deleted', 1);
        $this->getJson('/api/alerts')->assertJsonCount(0, 'alerts')->assertJsonPath('deleted_count', 3);
    }

    public function test_restore_brings_back_deleted_alerts(): void
    {
        $me = User::factory()->create();
        $this->alertFor($me);
        $this->alertFor($me);
        Sanctum::actingAs($me);

        $this->postJson('/api/alerts/clear')->assertOk();
        $this->postJson('/api/alerts/restore')->assertOk()->assertJsonPath('restored', 2);
        $this->getJson('/api/alerts')->assertJsonCount(2, 'alerts')->assertJsonPath('deleted_count', 0);
    }

    public function test_cannot_delete_other_users_alerts(): void
    {
        $other = User::factory()->create();
        $theirs = $this->alertFor($other);
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/alerts/{$theirs->id}")->assertNotFound();
        $this->postJson('/api/alerts/clear', ['ids' => [$theirs->id]])->assertOk()->assertJsonPath('deleted', 0);
        $this->postJson('/api/alerts/clear')->assertOk();
        $this->assertNull($theirs->fresh()->user_deleted_at);
    }

    public function test_admin_still_sees_deleted_alerts(): void
    {
        $me = User::factory()->create();
        $this->alertFor($me);
        Sanctum::actingAs($me);
        $this->postJson('/api/alerts/clear')->assertOk();

        $this->assertSame(1, Alert::count());
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/alerts/clear')->assertUnauthorized();
        $this->deleteJson('/api/alerts/1')->assertUnauthorized();
        $this->postJson('/api/alerts/restore')->assertUnauthorized();
    }
}
