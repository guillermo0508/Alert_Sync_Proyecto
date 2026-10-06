<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['user_id', 'source', 'status', 'latitude', 'longitude', 'message', 'contacts_notified', 'metadata'])]
class Alert extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'alerts';

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'contacts_notified' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
