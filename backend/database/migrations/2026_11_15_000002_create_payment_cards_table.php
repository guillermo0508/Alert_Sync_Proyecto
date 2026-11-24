<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varias tarjetas por usuario. La predeterminada se copia también en users.payment_card_*
 * para que la web y el cobro automático sigan funcionando igual.
 * Sólo se guardan los últimos 4 dígitos, titular, vencimiento y marca (nunca el número ni el CVV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 20)->nullable();
            $table->string('last_four', 4);
            $table->string('card_name', 120);
            $table->string('card_expiry', 5);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
    }
};
