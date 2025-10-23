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
            $table->string('MP_ACCESS_TOKEN')->nullable()->after('updated_at');
            $table->string('MP_PUBLIC_KEY')->nullable()->after('MP_ACCESS_TOKEN');
            $table->string('client_secret')->nullable()->after('MP_PUBLIC_KEY');
            $table->string('client_id')->nullable()->after('client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['MP_ACCESS_TOKEN', 'MP_PUBLIC_KEY', 'client_secret', 'client_id']);
        });
    }
};
