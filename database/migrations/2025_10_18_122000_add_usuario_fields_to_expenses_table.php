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
        // Laravel 11 ya no incluye doctrine/dbal, así que no se puede usar
        // getDoctrineSchemaManager(). Se intenta eliminar la FK por columnas
        // y, si no existe o el motor no la soporta, se ignora.
        try {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['usuario_id']);
            });
        } catch (Throwable $e) {
            // La FK no existe o el motor no soporta dropForeign (p. ej. SQLite).
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'usuario_id')) {
                $table->dropColumn('usuario_id');
            }
            if (Schema::hasColumn('expenses', 'usuario_nombre')) {
                $table->dropColumn('usuario_nombre');
            }
        });
    }
};
