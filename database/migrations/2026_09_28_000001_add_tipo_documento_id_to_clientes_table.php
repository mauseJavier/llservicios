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
            // Tipo de documento AFIP del receptor: 80=CUIT, 86=CUIL, 87=CDI, 96=DNI, 99=Sin identificar.
            // Se deja nullable para que los clientes existentes se infieran desde su DNI/CUIT.
            $table->unsignedTinyInteger('tipo_documento_id')
                ->nullable()
                ->after('condicion_iva_id')
                ->comment('Tipo de documento AFIP del receptor: 80=CUIT, 96=DNI, 99=Sin identificar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('tipo_documento_id');
        });
    }
};
