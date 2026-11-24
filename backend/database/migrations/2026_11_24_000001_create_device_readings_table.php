<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lecturas periódicas que envían el celular y el reloj: signos vitales,
     * ubicación y batería. La web las consulta para el panel "En vivo".
     */
    public function up(): void
    {
        Schema::create('device_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device', 20);                 // watch | mobile
            $table->unsignedSmallInteger('heart_rate')->nullable();   // lpm
            $table->unsignedTinyInteger('spo2')->nullable();          // %
            $table->unsignedInteger('steps')->nullable();
            $table->decimal('skin_temp', 4, 1)->nullable();           // °C
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('accuracy')->nullable();     // metros
            $table->unsignedTinyInteger('battery')->nullable();       // %
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['user_id', 'recorded_at']);
            $table->index(['user_id', 'device', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_readings');
    }
};
