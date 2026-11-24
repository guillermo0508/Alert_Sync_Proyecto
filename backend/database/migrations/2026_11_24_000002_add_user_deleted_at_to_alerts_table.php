<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alertas que el usuario borró de su historial. Se ocultan en la web, el
     * celular y el reloj, pero el registro se conserva para el panel de administración.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            $table->timestamp('user_deleted_at')->nullable()->after('metadata');
            $table->index(['user_id', 'user_deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'user_deleted_at']);
            $table->dropColumn('user_deleted_at');
        });
    }
};
