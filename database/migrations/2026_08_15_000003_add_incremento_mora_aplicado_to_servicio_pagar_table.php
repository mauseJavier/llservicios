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
        Schema::table('servicio_pagar', function (Blueprint $table) {
            $table->boolean('incremento_mora_aplicado')->default(false)->after('estado')->comment('Indica si ya se aplicó el recargo por mora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio_pagar', function (Blueprint $table) {
            $table->dropColumn('incremento_mora_aplicado');
        });
    }
};