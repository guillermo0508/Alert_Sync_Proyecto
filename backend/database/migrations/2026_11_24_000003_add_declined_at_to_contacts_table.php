<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El contacto tiene 15 minutos para aceptar; puede rechazar desde el correo.
     * Las invitaciones pendientes anteriores (que duraban 7 días) se acortan a
     * 15 minutos desde ahora para que todas sigan la misma regla.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->timestamp('declined_at')->nullable()->after('verification_token_expires_at');
        });

        DB::table('contacts')
            ->where('verified', false)
            ->where('verification_token_expires_at', '>', now()->addMinutes(15))
            ->update(['verification_token_expires_at' => now()->addMinutes(15)]);
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('declined_at');
        });
    }
};
