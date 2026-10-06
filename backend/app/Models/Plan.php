<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['plan_id', 'name', 'price_amount', 'features', 'max_contacts'])]
class Plan extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'plans';

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'features' => 'array',
            'max_contacts' => 'integer',
        ];
    }

    public static function defaults(): array
    {
        return [
            'basico' => [
                'plan_id' => 'basico',
                'name' => 'Básico',
                'price_amount' => 100,
                'features' => [
                    'Hasta 5 contactos de emergencia',
                    'Soporte para Smart Watch',
                    'Alertas instantáneas',
                ],
                'max_contacts' => 5,
            ],
            'premium' => [
                'plan_id' => 'premium',
                'name' => 'Premium',
                'price_amount' => 200,
                'features' => [
                    'Hasta 15 contactos de emergencia',
                    'Soporte para Smart Watch y Alexa',
                    'Dashboard exclusivo Premium',
                    'Soporte prioritario',
                ],
                'max_contacts' => 15,
            ],
        ];
    }

    public static function getOrDefault(string $planId): self
    {
        $plan = static::where('plan_id', $planId)->first();

        if ($plan) {
            return $plan;
        }

        $defaults = static::defaults();

        return new static($defaults[$planId] ?? $defaults['basico']);
    }
}
