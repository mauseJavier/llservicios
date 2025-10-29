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
        Schema::table('servicio_pagar', function (Blueprint $table) {
            // Agregar fecha de vencimiento
            $table->date('fecha_vencimiento')->nullable()->after('id');
            
            // Agregar comentario
            $table->text('comentario')->nullable()->after('fecha_vencimiento');
            
            // Agregar periodo del servicio (almacena el primer día del mes seleccionado)
            $table->date('periodo_servicio')->nullable()->after('comentario')->comment('Primer día del mes del período');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio_pagar', function (Blueprint $table) {
            // Eliminar las columnas en caso de rollback
            $table->dropColumn(['fecha_vencimiento', 'comentario', 'periodo_servicio']);
        });
    }
};
