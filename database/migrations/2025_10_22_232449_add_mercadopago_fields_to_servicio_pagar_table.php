<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servicio_pagar', function (Blueprint $table) {
            // Verificar si la columna mp_preference_id no existe antes de agregarla
            if (!Schema::hasColumn('servicio_pagar', 'mp_preference_id')) {
                $table->string('mp_preference_id')->nullable()->after('estado');
            }
            
            // Verificar si la columna mp_payment_id no existe antes de agregarla
            if (!Schema::hasColumn('servicio_pagar', 'mp_payment_id')) {
                $table->string('mp_payment_id')->nullable()->after('mp_preference_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio_pagar', function (Blueprint $table) {
            // Verificar si las columnas existen antes de eliminarlas
            if (Schema::hasColumn('servicio_pagar', 'mp_preference_id')) {
                $table->dropColumn('mp_preference_id');
            }
            
            if (Schema::hasColumn('servicio_pagar', 'mp_payment_id')) {
                $table->dropColumn('mp_payment_id');
            }
        });
    }
};
