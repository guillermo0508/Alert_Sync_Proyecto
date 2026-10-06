<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminUserRegistered extends Mailable
{
    use SerializesModels;

    public $userName;
    public $userEmail;
    public $userPhone;
    public $plan;
    public $username;

    public function __construct($userName, $userEmail, $userPhone, $plan, $username = null)
    {
        $this->userName = $userName;
        $this->userEmail = $userEmail;
        $this->userPhone = $userPhone;
        $this->plan = $plan;
        $this->username = $username;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cuenta en ALERT SYNC fue creada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-user-registered',
        );
    }
}
