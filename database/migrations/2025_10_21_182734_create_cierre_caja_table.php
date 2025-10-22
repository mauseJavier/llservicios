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
        Schema::create('cierre_caja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('usuario_nombre');
            $table->decimal('importe', 10, 2);
            $table->unsignedBigInteger('empresa_id');
            $table->enum('movimiento', ['inicio', 'cierre']);
            $table->text('comentario')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index('usuario_id');
            $table->index('empresa_id');
            $table->index('movimiento');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierre_caja');
    }
};
