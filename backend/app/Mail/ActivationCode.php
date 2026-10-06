<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivationCode extends Mailable
{
    use SerializesModels;

    public $userName;
    public $code;
    public $expiresInMinutes;

    public function __construct($userName, $code, $expiresInMinutes = 3)
    {
        $this->userName = $userName;
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación - ALERT SYNC',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.activation-code',
        );
    }
}
