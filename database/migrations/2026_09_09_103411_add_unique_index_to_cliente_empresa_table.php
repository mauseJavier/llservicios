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
        Schema::table('cliente_empresa', function (Blueprint $table) {
            $table->unique(['cliente_id', 'empresa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cliente_empresa', function (Blueprint $table) {
            $table->dropUnique(['cliente_id', 'empresa_id']);
        });
    }
};
