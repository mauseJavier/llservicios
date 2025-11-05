<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_qr_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadopago_pos_id')->constrained('mercadopago_pos')->onDelete('cascade');
            $table->string('external_reference')->unique()->comment('Referencia externa única');
            $table->string('in_store_order_id')->nullable()->comment('ID de la orden en MercadoPago');
            $table->decimal('total_amount', 10, 2)->comment('Monto total de la orden');
            $table->string('status')->default('pending')->comment('pending, paid, cancelled, expired');
            $table->string('payment_id')->nullable()->comment('ID del pago cuando se concrete');
            $table->string('payment_status')->nullable()->comment('Estado del pago de MP');
            $table->text('items')->nullable()->comment('Items de la orden en JSON');
            $table->text('notification_data')->nullable()->comment('Datos de la notificación webhook');
            $table->timestamp('paid_at')->nullable()->comment('Fecha y hora del pago');
            $table->timestamp('expires_at')->nullable()->comment('Fecha de expiración de la orden');
            $table->timestamps();
            
            $table->index('external_reference');
            $table->index('status');
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_qr_orders');
    }
};
