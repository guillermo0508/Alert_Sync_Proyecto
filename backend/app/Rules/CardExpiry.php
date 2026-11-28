<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Vencimiento de tarjeta en formato MM/AA, con mes 01–12 y que no esté vencida. */
class CardExpiry implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $value, $m)) {
            $fail('El vencimiento debe ser MM/AA (mes y año).');

            return;
        }
        $current = ((int) now()->format('y')) * 100 + (int) now()->format('n');
        if (((int) $m[2]) * 100 + (int) $m[1] < $current) {
            $fail('La tarjeta está vencida.');
        }
    }
}
