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
            'UPDATE cliente_empresa
             SET aplicar_recargos = (
                 SELECT c.aplicar_recargos FROM clientes c WHERE c.id = cliente_empresa.cliente_id
             )
             WHERE EXISTS (
                 SELECT 1 FROM clientes c WHERE c.id = cliente_empresa.cliente_id
             )'
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
            'UPDATE clientes
             SET aplicar_recargos = (
                 SELECT MAX(ce.aplicar_recargos) FROM cliente_empresa ce WHERE ce.cliente_id = clientes.id
             )
             WHERE EXISTS (
                 SELECT 1 FROM cliente_empresa ce WHERE ce.cliente_id = clientes.id
             )'
        );

        Schema::table('cliente_empresa', function (Blueprint $table) {
            $table->dropColumn('aplicar_recargos');
        });
    }
};
