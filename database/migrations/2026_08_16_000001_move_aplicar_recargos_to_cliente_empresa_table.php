<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mueve el flag 'aplicar_recargos' de la tabla clientes (compartida entre empresas)
     * a la tabla pivot cliente_empresa para que cada empresa decida independientemente.
     */
    public function up(): void
    {
        Schema::table('cliente_empresa', function (Blueprint $table) {
            $table->boolean('aplicar_recargos')->default(false)->after('empresa_id')->comment('Si el cliente aplica recargos por mora para esta empresa');
        });

        DB::statement(
            'UPDATE cliente_empresa ce
             INNER JOIN clientes c ON c.id = ce.cliente_id
             SET ce.aplicar_recargos = c.aplicar_recargos'
        );

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('aplicar_recargos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('aplicar_recargos')->default(false)->after('domicilio')->comment('Si el cliente aplica recargos por mora');
        });

        DB::statement(
            'UPDATE clientes c
             INNER JOIN (
                SELECT ce.cliente_id, MAX(ce.aplicar_recargos) AS aplicar_recargos
                FROM cliente_empresa ce
                GROUP BY ce.cliente_id
             ) p ON p.cliente_id = c.id
             SET c.aplicar_recargos = p.aplicar_recargos'
        );

        Schema::table('cliente_empresa', function (Blueprint $table) {
            $table->dropColumn('aplicar_recargos');
        });
    }
};
