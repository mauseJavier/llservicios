<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra cada intento de pago agrupado (link cliente_impagos) con una
     * referencia única e irrepetible, para evitar que un pago se aplique a
     * deudas de meses distintos.
     */
    public function up(): void
    {
        Schema::create('mp_pago_intentos', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('referencia')->unique()->comment('external_reference enviado a MercadoPago (lote_{token})');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('empresa_id');
            $table->string('mp_preference_id')->nullable();
            $table->enum('estado', ['pendiente', 'pago'])->default('pendiente');
            $table->decimal('monto', 12, 2)->nullable();
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('empresa_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_pago_intentos');
    }
};
