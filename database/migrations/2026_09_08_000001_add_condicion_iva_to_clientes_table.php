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
        Schema::table('clientes', function (Blueprint $table) {
            // Condición frente al IVA del receptor (RG 5616 / ARCA)
            // 1=Responsable Inscripto, 6=Monotributo, 5=Consumidor Final, etc.
            $table->unsignedTinyInteger('condicion_iva_id')
                ->nullable()
                ->default(5)
                ->after('domicilio')
                ->comment('Condición frente al IVA del cliente (RG 5616): 1=Responsable Inscripto, 6=Monotributo, 5=Consumidor Final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('condicion_iva_id');
        });
    }
};
