<?php

namespace App\Rules;

use App\Models\PaymentCard;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/** CVV: 4 dígitos si la tarjeta es AMEX, 3 para las demás marcas. */
class CardCvv implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $length = PaymentCard::cvvLength((string) ($this->data['card_number'] ?? ''));
        if (! preg_match('/^\d{'.$length.'}$/', (string) $value)) {
            $fail("El CVV debe tener {$length} dígitos.");
        }
    }
}
