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
        Schema::table('pagos', function (Blueprint $table) {
            // Agregar forma_pago2 después de la columna importe
            $table->string('forma_pago2')->nullable()->after('importe');
            
            // Agregar importe2 después de forma_pago2
            $table->decimal('importe2', 10, 2)->nullable()->after('forma_pago2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Eliminar las columnas en caso de rollback
            $table->dropColumn(['forma_pago2', 'importe2']);
        });
    }
};
