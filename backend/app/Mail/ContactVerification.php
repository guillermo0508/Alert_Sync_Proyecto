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

    public function __construct($contactName, $ownerName, $verifyUrl)
    {
        $this->contactName = $contactName;
        $this->ownerName = $ownerName;
        $this->verifyUrl = $verifyUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->ownerName} te agregó como contacto de emergencia - ALERT SYNC",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-verification',
        );
    }
}
