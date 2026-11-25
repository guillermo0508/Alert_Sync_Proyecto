<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id', 'name', 'phone', 'email', 'relationship', 'notify_sms', 'notify_call', 'notify_email', 'priority',
    'verified', 'verification_token', 'verification_token_expires_at', 'declined_at',
])]
#[Hidden(['verification_token'])]
class Contact extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_REJECTED = 'rejected';
    /** Sin correo: no se le puede pedir confirmación. */
    public const STATUS_NO_EMAIL = 'no_email';

    /** Minutos que tiene el contacto para aceptar antes de quedar como rechazado. */
    public const RESPONSE_MINUTES = 15;

    protected $appends = ['status'];

    /**
     * confirmed: aceptó · pending: tiene menos de 15 min para responder ·
     * rejected: rechazó desde el correo o no respondió a tiempo.
     */
    public function getStatusAttribute(): string
    {
        if ($this->verified) {
            return self::STATUS_CONFIRMED;
        }
        if (! $this->email) {
            return self::STATUS_NO_EMAIL;
        }
        if ($this->declined_at || ! $this->verification_token_expires_at || $this->verification_token_expires_at->isPast()) {
            return self::STATUS_REJECTED;
        }

        return self::STATUS_PENDING;
    }

    protected function casts(): array
    {
        return [
            'notify_sms' => 'boolean',
            'notify_call' => 'boolean',
            'notify_email' => 'boolean',
            'priority' => 'integer',
            'verified' => 'boolean',
            'verification_token_expires_at' => 'datetime',
            'declined_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
