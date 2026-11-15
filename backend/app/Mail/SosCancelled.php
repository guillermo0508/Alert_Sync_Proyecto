<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a los contactos de que una alerta SOS fue cancelada por el usuario.
 */
class SosCancelled extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $contactName,
        public string $ownerName,
        public string $sentAt,
        public string $cancelledAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ Alerta cancelada: {$this->ownerName} está a salvo - ALERT SYNC",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sos-cancelled');
    }
}
