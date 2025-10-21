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
        Schema::table('expenses', function (Blueprint $table) {
            // usuario_id como unsignedBigInteger nullable y FK a users.id (si existe tabla users)
            $table->unsignedBigInteger('usuario_id')->nullable()->after('comentario');
            $table->string('usuario_nombre')->nullable()->after('usuario_id');

            // añadir la constraint FK si la tabla users existe
            if (Schema::hasTable('users')) {
                $table->foreign('usuario_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Eliminar FK si existe
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = null;
            try {
                $doctrineTable = $sm->listTableDetails($table->getTable());
            } catch (\Exception $e) {
                // noop
            }

            if ($doctrineTable && $doctrineTable->hasForeignKey('expenses_usuario_id_foreign')) {
                $table->dropForeign('expenses_usuario_id_foreign');
            } elseif ($doctrineTable) {
                // intentar eliminar por columnas si el nombre es distinto
                try {
                    $table->dropForeign([$table->getTable().'_usuario_id_foreign']);
                } catch (\Exception $e) {
                    // noop
                }
            }

            // Eliminar columnas si existen
            if (Schema::hasColumn('expenses', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
            if (Schema::hasColumn('expenses', 'usuario_nombre')) {
                $table->dropColumn('usuario_nombre');
            }
        });
    }
};
