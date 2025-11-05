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
        Schema::table('mercadopago_pos', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('mp_pos_id')->comment('UUID único del QR en MercadoPago');
            $table->string('status')->default('active')->after('uuid')->comment('Estado de la caja: active, inactive');
            $table->text('qr_data')->nullable()->after('qr_url')->comment('Datos del código QR (string largo)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mercadopago_pos', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'status', 'qr_data']);
        });
    }
};
