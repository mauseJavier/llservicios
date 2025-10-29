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
        Schema::table('servicios', function (Blueprint $table) {
            // Agregar diasVencimiento con valor por defecto de 10 días
            $table->integer('diasVencimiento')->default(10)->after('id');
            
            // Agregar precio2 y precio3 después de la columna precio
            $table->decimal('precio2', 10, 2)->nullable()->after('precio');
            $table->decimal('precio3', 10, 2)->nullable()->after('precio2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            // Eliminar las columnas en caso de rollback
            $table->dropColumn(['diasVencimiento', 'precio2', 'precio3']);
        });
    }
};
