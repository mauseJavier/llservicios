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
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('incremento_mora_tipo')->nullable()->after('diasVencimiento')->comment('null = sin recargo, fijo, porcentaje');
            $table->decimal('incremento_mora_valor', 10, 2)->nullable()->after('incremento_mora_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn(['incremento_mora_tipo', 'incremento_mora_valor']);
        });
    }
};