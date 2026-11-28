<?php

namespace App\Rules;

use App\Models\PaymentCard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Número de tarjeta: 15 dígitos si es AMEX, 16 para las demás marcas. */
class CardNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $number = (string) $value;
        if (! preg_match('/^\d+$/', $number)) {
            $fail('El número de tarjeta solo puede tener dígitos.');

            return;
        }
        $length = PaymentCard::numberLength($number);
        if (strlen($number) !== $length) {
            $fail("El número de tarjeta debe tener {$length} dígitos.");
        }
    }
}
