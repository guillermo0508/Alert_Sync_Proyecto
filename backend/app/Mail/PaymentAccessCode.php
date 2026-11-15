<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Código para entrar a "Método de pago" en la app. */
class PaymentAccessCode extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $userName,
        public string $code,
        public int $expiresInMinutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Código para ver tu método de pago - ALERT SYNC');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-access-code');
    }
}
