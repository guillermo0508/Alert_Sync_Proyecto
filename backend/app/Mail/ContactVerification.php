<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactVerification extends Mailable
{
    use SerializesModels;

    public $contactName;
    public $ownerName;
    public $verifyUrl;
    public $declineUrl;
    public $responseMinutes;

    public function __construct($contactName, $ownerName, $verifyUrl, $declineUrl = null, $responseMinutes = 15)
    {
        $this->contactName = $contactName;
        $this->ownerName = $ownerName;
        $this->verifyUrl = $verifyUrl;
        $this->declineUrl = $declineUrl;
        $this->responseMinutes = $responseMinutes;
    }

    public function envelope(): Envelope
    {
        // La hora hace único el asunto: así Gmail no mete cada reenvío dentro de la conversación
        // del primer correo (el contacto no lo veía como un correo nuevo).
        $time = now()->timezone('America/Mexico_City')->format('h:i A');

        return new Envelope(
            subject: "{$this->ownerName} te agregó como contacto de emergencia ({$time}) - ALERT SYNC",
        );
    }

    public function content(): Content
    {
        // Versión HTML + texto plano: los filtros de Gmail confían más en correos con ambas.
        return new Content(
            view: 'emails.contact-verification',
            text: 'emails.contact-verification-text',
        );
    }
}
