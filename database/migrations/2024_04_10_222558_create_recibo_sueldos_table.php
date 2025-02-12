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
        Schema::create('recibo_sueldos', function (Blueprint $table) {
            $table->id();
            $table->string('periodo');
            $table->string('empleador');
            $table->string('apellidoNombre')->default('Ejemplo');
            $table->unsignedBigInteger('cuil');
            $table->unsignedInteger('legajo');
            $table->date('fechaIngreso')->nullable();
            $table->string('categoria')->nullable();
            $table->longText('datos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibo_sueldos');
    }
};
