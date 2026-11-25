<?php

namespace Tests\Feature;

use App\Mail\ContactVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactVerificationLinkTest extends TestCase
{
    use RefreshDatabase;

    private function saveContact(array $headers = []): string
    {
        Mail::fake();
        Sanctum::actingAs(User::factory()->create(['plan' => 'Premium']));
        $this->postJson('/api/contacts', ['name' => 'Mamá', 'email' => 'mama@example.com'], $headers)->assertCreated();

        $url = null;
        Mail::assertSent(ContactVerification::class, function ($mail) use (&$url) {
            $url = $mail->verifyUrl;

            return true;
        });

        return $url;
    }

    public function test_link_points_to_the_website_the_contact_was_saved_from(): void
    {
        $url = $this->saveContact(['Origin' => 'https://alertsync-web.onrender.com']);

        $this->assertStringStartsWith('https://alertsync-web.onrender.com/verify-contact/', $url);
        $this->assertStringContainsString('?token=', $url);
    }

    public function test_untrusted_origin_is_ignored_and_frontend_url_is_used(): void
    {
        config(['app.frontend_url' => 'https://alertsync-web.onrender.com,http://localhost:5173']);

        $url = $this->saveContact(['Origin' => 'https://evil.example.com']);

        $this->assertStringStartsWith('https://alertsync-web.onrender.com/verify-contact/', $url);
    }

    public function test_without_origin_uses_frontend_url(): void
    {
        config(['app.frontend_url' => 'https://alertsync-web.onrender.com/']);

        $url = $this->saveContact();

        $this->assertStringStartsWith('https://alertsync-web.onrender.com/verify-contact/', $url);
    }
}
