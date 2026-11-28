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

    /** Detecta la marca por los primeros dígitos (igual que en la app y la web). */
    public static function detectBrand(string $number): string
    {
        $d = preg_replace('/\D/', '', $number);
        $p2 = (int) substr($d, 0, 2);
        $p3 = (int) substr($d, 0, 3);
        $p4 = (int) substr($d, 0, 4);

        return match (true) {
            str_starts_with($d, '4') => 'VISA',
            $p2 === 34 || $p2 === 37 => 'AMEX',
            ($p2 >= 51 && $p2 <= 55) || ($p4 >= 2221 && $p4 <= 2720) => 'MASTERCARD',
            $p4 === 6011 || $p2 === 65 || ($p3 >= 644 && $p3 <= 649) => 'DISCOVER',
            default => 'TARJETA',
        };
    }

    /** AMEX: 15 dígitos; las demás: 16. */
    public static function numberLength(string $number): int
    {
        return static::detectBrand($number) === 'AMEX' ? 15 : 16;
    }

    /** AMEX: CVV de 4; las demás: 3. */
    public static function cvvLength(string $number): int
    {
        return static::detectBrand($number) === 'AMEX' ? 4 : 3;
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
