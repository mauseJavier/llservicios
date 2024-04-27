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
        Schema::create('formato_registro_recibos', function (Blueprint $table) {
            $table->id();

            $table->enum('tipo', ['ingresos', 'deducciones','total']);
            $table->string('codigo')->nullable();
            $table->string('descripcion');
            $table->string('cantidad')->nullable();
            $table->string('importe');
            $table->unsignedInteger('empresa_id');

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formato_registro_recibos');
    }
};
