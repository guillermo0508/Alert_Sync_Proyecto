<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'plan', 'amount', 'currency', 'card_last_four', 'card_name', 'status', 'note'])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
