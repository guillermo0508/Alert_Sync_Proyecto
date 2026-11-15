<?php

namespace Tests\Feature;

use App\Mail\SosAlert;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MailRelayTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'mail.default' => 'relay',
            'mail.mailers.relay.endpoint' => 'https://relay.example.vercel.app/api/send',
            'mail.mailers.relay.secret' => 'secreto-de-prueba',
            'mail.from.address' => 'alertsync26@gmail.com',
            'mail.from.name' => 'Alert Sync',
        ]);
    }

    public function test_sos_mail_is_posted_to_relay_with_secret(): void
    {
        Http::fake(['relay.example.vercel.app/*' => Http::response(['ok' => true], 200)]);

        Mail::to('mama@example.com')->send(
            new SosAlert('Mamá', 'Ana', 'Ayuda', '14/11/2026 10:30 PM', 'https://maps.example', null)
        );

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://relay.example.vercel.app/api/send'
                && $request->hasHeader('Authorization', 'Bearer secreto-de-prueba')
                && $request['to'] === ['mama@example.com']
                && str_contains($request['subject'], 'ALERTA SOS')
                && str_contains($request['html'], 'https://maps.example')
                && $request['from_name'] === 'Alert Sync';
        });
    }

    public function test_relay_error_is_reported(): void
    {
        Http::fake(['relay.example.vercel.app/*' => Http::response(['ok' => false, 'error' => 'SMTP'], 502)]);

        $this->expectException(TransportException::class);
        Mail::to('mama@example.com')->send(
            new SosAlert('Mamá', 'Ana', 'Ayuda', '14/11/2026 10:30 PM')
        );
    }
}
