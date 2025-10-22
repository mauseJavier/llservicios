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
        Schema::table('expenses', function (Blueprint $table) {
            // Agregar nueva columna para forma_pago_id
            $table->unsignedBigInteger('forma_pago_id')->nullable()->after('detalle');
            $table->foreign('forma_pago_id')->references('id')->on('forma_pagos');
            
            // Cambiar estado para que solo permita 'pago' e 'impago'
            $table->enum('estado_nuevo', ['pago', 'impago'])->default('impago')->after('forma_pago_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['forma_pago_id']);
            $table->dropColumn('forma_pago_id');
            $table->dropColumn('estado_nuevo');
        });
    }
};
