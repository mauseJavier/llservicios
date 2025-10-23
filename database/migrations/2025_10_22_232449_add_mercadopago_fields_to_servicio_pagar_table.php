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
            $table->string('mp_preference_id')->nullable()->after('estado');
            $table->string('mp_payment_id')->nullable()->after('mp_preference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicio_pagar', function (Blueprint $table) {
            $table->dropColumn(['mp_preference_id', 'mp_payment_id']);
        });
    }
};
