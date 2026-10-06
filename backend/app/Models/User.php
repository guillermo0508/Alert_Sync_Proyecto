<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Auth\User as Authenticatable;

#[Fillable([
    'name', 'email', 'username', 'phone', 'password', 'plan', 'plan_expires_at', 'next_plan', 'devices', 'device_names', 'role',
    'status', 'activation_code', 'activation_code_expires_at', 'activation_token', 'activation_token_expires_at',
    'password_reset_code', 'password_reset_code_expires_at', 'password_reset_token', 'password_reset_token_expires_at',
    'payment_card_last_four', 'payment_card_name', 'payment_card_expiry', 'auto_renew',
])]
#[Hidden([
    'password', 'remember_token', 'activation_code', 'activation_token',
    'password_reset_code', 'password_reset_token',
])]
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'mongodb';

    protected $collection = 'users';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'devices' => 'array',
            'device_names' => 'array',
            'plan_expires_at' => 'datetime',
            'activation_code_expires_at' => 'datetime',
            'activation_token_expires_at' => 'datetime',
            'password_reset_code_expires_at' => 'datetime',
            'password_reset_token_expires_at' => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'user_id');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }
}
