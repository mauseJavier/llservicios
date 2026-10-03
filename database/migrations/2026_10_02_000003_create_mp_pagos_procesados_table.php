<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guard de idempotencia: un payment_id de MercadoPago se aplica una sola vez,
     * aunque la reconciliación o un webhook tardío lo vuelvan a encontrar.
     */
    public function up(): void
    {
        Schema::create('mp_pagos_procesados', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique();
            $table->string('external_reference')->nullable();
            $table->unsignedBigInteger('intento_id')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->string('estado', 30)->nullable();
            $table->timestamps();

            $table->index('external_reference');
            $table->index('intento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_pagos_procesados');
    }
};
