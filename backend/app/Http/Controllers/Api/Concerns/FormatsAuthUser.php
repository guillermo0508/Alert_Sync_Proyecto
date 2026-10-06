<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;

trait FormatsAuthUser
{
    protected function formatUser(User $user): array
    {
        return [
            'id' => (string) $user->_id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username ?? null,
            'phone' => $user->phone,
            'plan' => $user->plan ?? 'Demo',
            'plan_expires_at' => $user->plan_expires_at ? $user->plan_expires_at->toIso8601String() : null,
            'next_plan' => $user->next_plan,
            'devices' => $user->devices ?? ['watch' => false, 'alexa' => false],
            'device_names' => $user->device_names ?? ['watch' => null, 'alexa' => null],
            'role' => $user->role ?? 'user',
            'payment_method' => [
                'has_card' => ! empty($user->payment_card_last_four),
                'card_last_four' => $user->payment_card_last_four,
                'card_name' => $user->payment_card_name,
                'card_expiry' => $user->payment_card_expiry,
                'auto_renew' => (bool) ($user->auto_renew ?? false),
            ],
        ];
    }
}
