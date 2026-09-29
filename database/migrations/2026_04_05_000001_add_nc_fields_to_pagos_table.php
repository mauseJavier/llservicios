<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El nombre del índice UNIQUE puede variar entre entornos;
        // se elimina cualquier UNIQUE que afecte a id_servicio_pagar.
        $indicesUnicos = DB::select(
            "SHOW INDEX FROM pagos WHERE Column_name = 'id_servicio_pagar' AND Non_unique = 0"
        );

        foreach ($indicesUnicos as $index) {
            DB::statement('ALTER TABLE pagos DROP INDEX `' . $index->Key_name . '`');
        }

        Schema::table('pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('pagos', 'afip_nc_de_pago_id')) {
                $table->unsignedBigInteger('afip_nc_de_pago_id')
                    ->nullable()
                    ->after('afip_punto_venta')
                    ->comment('ID del pago original del cual se emitio esta Nota de Credito');

                $table->foreign('afip_nc_de_pago_id')
                    ->references('id')
                    ->on('pagos')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (Schema::hasColumn('pagos', 'afip_nc_de_pago_id')) {
                $foreign = DB::selectOne(
                    "SELECT CONSTRAINT_NAME AS name
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'pagos'
                       AND COLUMN_NAME = 'afip_nc_de_pago_id'
                       AND REFERENCED_TABLE_NAME IS NOT NULL
                     LIMIT 1"
                );

                if ($foreign && !empty($foreign->name)) {
                    DB::statement('ALTER TABLE pagos DROP FOREIGN KEY `' . $foreign->name . '`');
                }

                $table->dropColumn('afip_nc_de_pago_id');
            }

            $duplicados = DB::table('pagos')
                ->select('id_servicio_pagar', DB::raw('COUNT(*) as c'))
                ->groupBy('id_servicio_pagar')
                ->having('c', '>', 1)
                ->exists();

            if (!$duplicados) {
                $table->unique('id_servicio_pagar');
            }
        });
    }
};
