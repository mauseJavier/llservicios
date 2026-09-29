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
        if (!Schema::hasTable('cliente_segmento')) {
            Schema::create('cliente_segmento', function (Blueprint $table) {
                $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
                $table->foreignId('segmento_id')->constrained()->cascadeOnDelete();
                $table->primary(['cliente_id', 'segmento_id']);

                $table->index('cliente_id');
                $table->index('segmento_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_segmento');
    }
};
