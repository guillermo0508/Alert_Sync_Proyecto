<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo de emergencia que reciben los contactos cuando el usuario presiona SOS.
 */
class SosAlert extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $contactName,
        public string $ownerName,
        public string $alertMessage,
        public string $sentAt,
        public ?string $mapsUrl = null,
        public ?string $ownerPhone = null,
        public ?string $bloodType = null,
        public ?string $allergies = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 ALERTA SOS: {$this->ownerName} necesita ayuda - ALERT SYNC",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sos-alert');
    }
}
