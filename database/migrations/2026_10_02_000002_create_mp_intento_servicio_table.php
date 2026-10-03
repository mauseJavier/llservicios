<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot exacto de las deudas (servicio_pagar) incluidas en un intento de pago.
     */
    public function up(): void
    {
        Schema::create('mp_intento_servicio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intento_id');
            $table->unsignedBigInteger('servicio_pagar_id');
            $table->timestamps();

            $table->unique(['intento_id', 'servicio_pagar_id'], 'mp_intento_servicio_unique');
            $table->index('servicio_pagar_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_intento_servicio');
    }
};
