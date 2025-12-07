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
        Schema::table('empresas', function (Blueprint $table) {
            // Verificar si la columna instanciaWS no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'instanciaWS')) {
                $table->string('instanciaWS')->nullable()->after('client_id');
            }
            
            // Verificar si la columna tokenWS no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'tokenWS')) {
                $table->string('tokenWS')->nullable()->after('instanciaWS');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Verificar si las columnas existen antes de eliminarlas
            if (Schema::hasColumn('empresas', 'instanciaWS')) {
                $table->dropColumn('instanciaWS');
            }
            
            if (Schema::hasColumn('empresas', 'tokenWS')) {
                $table->dropColumn('tokenWS');
            }
        });
    }
};
