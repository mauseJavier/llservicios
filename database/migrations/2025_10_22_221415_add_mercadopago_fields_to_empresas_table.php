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
            // Verificar si la columna MP_ACCESS_TOKEN no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'MP_ACCESS_TOKEN')) {
                $table->string('MP_ACCESS_TOKEN')->nullable()->after('updated_at');
            }
            
            // Verificar si la columna MP_PUBLIC_KEY no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'MP_PUBLIC_KEY')) {
                $table->string('MP_PUBLIC_KEY')->nullable()->after('MP_ACCESS_TOKEN');
            }
            
            // Verificar si la columna client_secret no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'client_secret')) {
                $table->string('client_secret')->nullable()->after('MP_PUBLIC_KEY');
            }
            
            // Verificar si la columna client_id no existe antes de agregarla
            if (!Schema::hasColumn('empresas', 'client_id')) {
                $table->string('client_id')->nullable()->after('client_secret');
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
            if (Schema::hasColumn('empresas', 'MP_ACCESS_TOKEN')) {
                $table->dropColumn('MP_ACCESS_TOKEN');
            }
            
            if (Schema::hasColumn('empresas', 'MP_PUBLIC_KEY')) {
                $table->dropColumn('MP_PUBLIC_KEY');
            }
            
            if (Schema::hasColumn('empresas', 'client_secret')) {
                $table->dropColumn('client_secret');
            }
            
            if (Schema::hasColumn('empresas', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });
    }
};
