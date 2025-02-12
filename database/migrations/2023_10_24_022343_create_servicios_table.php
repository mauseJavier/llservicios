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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->longText('descripcion');
            $table->double('precio');
            $table->enum('tiempo', ['hora', 'dia', 'semana','mes'])->default('mes');
            $table->unsignedInteger('empresa_id')->default(1);
            $table->string('linkPago')->default('link.mercadopago.com.ar/laslajasoft')->nullable();
            $table->string('imagen')->default('https://cdn.icon-icons.com/icons2/1603/PNG/512/photo-photography-image-picture_108525.png')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
