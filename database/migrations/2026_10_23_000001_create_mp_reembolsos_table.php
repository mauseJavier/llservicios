<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de reembolsos de MercadoPago ejecutados desde el sistema.
     * El payment_id es único para evitar reembolsar dos veces el mismo pago.
     */
    public function up(): void
    {
        Schema::create('mp_reembolsos', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique();
            $table->string('external_reference')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->string('motivo')->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->string('mp_refund_id')->nullable();
            $table->json('respuesta')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('external_reference');
            $table->index('empresa_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_reembolsos');
    }
};
