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
        Schema::table('mercadopago_pos', function (Blueprint $table) {
            $table->foreignId('usuario_id')
                ->nullable()
                ->after('active')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario asignado a esta caja');
            
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mercadopago_pos', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropIndex(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
