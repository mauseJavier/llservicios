<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'dia_notificacion')) {
                $table->unsignedTinyInteger('dia_notificacion')->nullable()->after('tokenWS');
            }
        });

        // Configurar las empresas existentes con un día aleatorio entre 1 y 7
        $ids = DB::table('empresas')->whereNull('dia_notificacion')->pluck('id');

        foreach ($ids as $id) {
            DB::table('empresas')->where('id', $id)->update([
                'dia_notificacion' => random_int(1, 7),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            if (Schema::hasColumn('empresas', 'dia_notificacion')) {
                $table->dropColumn('dia_notificacion');
            }
        });
    }
};
