<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada empresa tiene su propia cuenta de Mercado Pago y, por lo tanto, su
     * propio secreto para validar la firma de los webhooks.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('empresas', 'MP_WEBHOOK_SECRET')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('MP_WEBHOOK_SECRET')->nullable()->after('MP_USER_ID');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empresas', 'MP_WEBHOOK_SECRET')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropColumn('MP_WEBHOOK_SECRET');
            });
        }
    }
};
