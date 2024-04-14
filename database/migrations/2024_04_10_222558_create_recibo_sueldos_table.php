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
            $table->string('apellidoNombre');
            $table->unsignedBigInteger('cuil');
            $table->unsignedInteger('legajo');
            $table->date('fechaIngreso');
            $table->string('categoria');
            $table->longText('datos');
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
