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
        if (!Schema::hasTable('segmentos')) {
            Schema::create('segmentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->string('color', 20)->nullable();
                $table->timestamps();

                $table->index('empresa_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segmentos');
    }
};
