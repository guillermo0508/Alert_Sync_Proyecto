<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha médica del usuario (se incluye en el correo SOS) y código de acceso
 * por correo para ver/editar el método de pago desde la app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('blood_type', 5)->nullable()->after('phone');
            $table->string('allergies', 500)->nullable()->after('blood_type');
            $table->string('payment_access_code')->nullable();
            $table->timestamp('payment_access_code_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['blood_type', 'allergies', 'payment_access_code', 'payment_access_code_expires_at']);
        });
    }
};
