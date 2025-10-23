<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Eliminar las columnas antiguas
            $table->dropColumn(['forma_pago', 'estado']);
            
            // Renombrar la nueva columna estado
            $table->renameColumn('estado_nuevo', 'estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Restaurar columnas antiguas
            $table->string('forma_pago')->after('detalle');
            $table->renameColumn('estado', 'estado_nuevo');
            $table->string('estado')->after('forma_pago');
        });
    }
};
