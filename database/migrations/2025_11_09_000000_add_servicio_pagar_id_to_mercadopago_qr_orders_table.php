<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mercadopago_qr_orders', function (Blueprint $table) {
            // Relación con la tabla servicio_pagar campo id
            $table->foreignId('servicio_pagar_id')
                ->nullable()
                ->after('mercadopago_pos_id')
                ->constrained('servicio_pagar')
                ->onDelete('cascade');
            
            $table->index('servicio_pagar_id');
        });
    }

    public function down(): void
    {
        Schema::table('mercadopago_qr_orders', function (Blueprint $table) {
            $table->dropForeign(['servicio_pagar_id']);
            $table->dropIndex(['servicio_pagar_id']);
            $table->dropColumn('servicio_pagar_id');
        });
    }
};
