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
        Schema::create('registros_cerradura', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('nombre_cerradura');
            $table->string('tipo')->nullable();
            $table->string('dispositivo_id')->nullable();
            $table->dateTime('fecha_hora');
            $table->dateTime('recibido_en')->nullable();
            $table->json('datos_extra')->nullable();
            $table->timestamps();

            // Índices para mejorar el rendimiento de las consultas
            $table->index('empresa_id');
            $table->index('cliente_id');
            $table->index(['empresa_id', 'fecha_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_cerradura');
    }
};
