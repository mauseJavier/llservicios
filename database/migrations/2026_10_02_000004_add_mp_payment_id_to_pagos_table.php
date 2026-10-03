<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos', 'mp_payment_id')) {
                $table->string('mp_payment_id')->nullable()->index()->after('comentario');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'mp_payment_id')) {
                $table->dropIndex(['mp_payment_id']);
                $table->dropColumn('mp_payment_id');
            }
        });
    }
};
