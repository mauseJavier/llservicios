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
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'clave_fiscal')) {
                $table->string('clave_fiscal')->nullable()->after('cuit');
            }

            if (!Schema::hasColumn('empresas', 'titular')) {
                $table->string('titular')->nullable()->after('clave_fiscal');
            }

            if (!Schema::hasColumn('empresas', 'aliasTranferencia')) {
                $table->string('aliasTranferencia')->nullable()->after('titular');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'clave_fiscal')) {
                $table->dropColumn('clave_fiscal');
            }

            if (Schema::hasColumn('empresas', 'titular')) {
                $table->dropColumn('titular');
            }

            if (Schema::hasColumn('empresas', 'aliasTranferencia')) {
                $table->dropColumn('aliasTranferencia');
            }
        });
    }
};
