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
            $table->string('afip_cae', 20)->nullable()->after('comentario')->comment('Código de Autorización Electrónico de AFIP');
            $table->date('afip_cae_vencimiento')->nullable()->after('afip_cae')->comment('Fecha de vencimiento del CAE');
            $table->integer('afip_numero_comprobante')->nullable()->after('afip_cae_vencimiento')->comment('Número de comprobante de AFIP');
            $table->integer('afip_tipo_comprobante')->nullable()->after('afip_numero_comprobante')->comment('Tipo de comprobante AFIP (1=Factura A, 6=Factura B, etc)');
            $table->integer('afip_punto_venta')->nullable()->after('afip_tipo_comprobante')->comment('Punto de venta usado en AFIP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn([
                'afip_cae',
                'afip_cae_vencimiento',
                'afip_numero_comprobante',
                'afip_tipo_comprobante',
                'afip_punto_venta'
            ]);
        });
    }
};
