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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'afip_punto_venta')) {
                $table->integer('afip_punto_venta')
                    ->nullable()
                    ->after('empresa_id')
                    ->comment('Punto de venta AFIP del usuario');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'afip_punto_venta')) {
                $table->dropColumn('afip_punto_venta');
            }
        });
    }
};
