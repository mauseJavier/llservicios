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
        //
        Schema::table('clientes', function (Blueprint $table) {

            // POR QUE GENERA ERROR EN LA MODIFICAIOCN EL SQL LITE 
            if( env('DB_CONNECTION') != 'sqlite' ){

                $table->string('correo')->default('correo@correo.com')->change(); 
            }


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
