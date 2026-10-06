<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['user_id', 'plan', 'amount', 'currency', 'card_last_four', 'card_name', 'status', 'note'])]
class Payment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'payments';

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
