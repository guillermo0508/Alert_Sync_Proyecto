<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'device', 'heart_rate', 'spo2', 'steps', 'skin_temp',
    'latitude', 'longitude', 'accuracy', 'battery', 'recorded_at'])]
class DeviceReading extends Model
{
    protected $hidden = ['user_id', 'updated_at'];

    protected function casts(): array
    {
        return [
            'heart_rate' => 'integer',
            'spo2' => 'integer',
            'steps' => 'integer',
            'skin_temp' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'integer',
            'battery' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
