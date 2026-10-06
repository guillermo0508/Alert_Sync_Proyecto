<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable([
    'user_id', 'name', 'phone', 'email', 'relationship', 'notify_sms', 'notify_call', 'notify_email', 'priority',
    'verified', 'verification_token', 'verification_token_expires_at',
])]
#[Hidden(['verification_token'])]
class Contact extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'contacts';

    protected function casts(): array
    {
        return [
            'notify_sms' => 'boolean',
            'notify_call' => 'boolean',
            'notify_email' => 'boolean',
            'priority' => 'integer',
            'verified' => 'boolean',
            'verification_token_expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
