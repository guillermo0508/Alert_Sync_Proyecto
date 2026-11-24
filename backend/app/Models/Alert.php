<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'source', 'status', 'latitude', 'longitude', 'message', 'contacts_notified', 'metadata', 'user_deleted_at'])]
class Alert extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'contacts_notified' => 'array',
            'metadata' => 'array',
            'user_deleted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
