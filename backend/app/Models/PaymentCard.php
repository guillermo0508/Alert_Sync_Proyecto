<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'brand', 'last_four', 'card_name', 'card_expiry', 'is_default'])]
class PaymentCard extends Model
{
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Detecta la marca por los primeros dígitos. */
    public static function detectBrand(string $number): string
    {
        return match (true) {
            str_starts_with($number, '4') => 'VISA',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $number) => 'MASTERCARD',
            (bool) preg_match('/^3[47]/', $number) => 'AMEX',
            (bool) preg_match('/^(6011|65)/', $number) => 'DISCOVER',
            default => 'TARJETA',
        };
    }

    /**
     * Marca esta tarjeta como la principal del usuario: es la que se usa para el cobro automático
     * del plan y la que aparece seleccionada al cambiar de plan.
     */
    public function makeDefaultFor(User $user): void
    {
        static::where('user_id', $user->id)->update(['is_default' => false]);
        $this->is_default = true;
        $this->save();

        // Copia en users para la web y el cobro automático (no cambia auto_renew).
        $user->payment_card_last_four = $this->last_four;
        $user->payment_card_name = $this->card_name;
        $user->payment_card_expiry = $this->card_expiry;
        $user->save();
    }
}
