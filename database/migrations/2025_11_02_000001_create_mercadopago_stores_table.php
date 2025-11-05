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
        Schema::create('mercadopago_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('external_id')->unique()->comment('ID externo para identificar la tienda');
            $table->string('mp_store_id')->nullable()->comment('ID de la tienda en MercadoPago');
            $table->string('name')->comment('Nombre de la tienda');
            $table->json('location')->nullable()->comment('Ubicación de la tienda');
            $table->string('address_street_name')->nullable();
            $table->string('address_street_number')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip_code')->nullable();
            $table->string('address_country')->default('AR');
            $table->decimal('address_latitude', 10, 8)->nullable();
            $table->decimal('address_longitude', 11, 8)->nullable();
            $table->timestamps();
            
            $table->index('empresa_id');
            $table->index('mp_store_id');
        });

        Schema::create('mercadopago_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mercadopago_store_id')->constrained('mercadopago_stores')->onDelete('cascade');
            $table->string('external_id')->unique()->comment('ID externo para identificar la caja');
            $table->string('mp_pos_id')->nullable()->comment('ID de la caja en MercadoPago');
            $table->string('name')->comment('Nombre de la caja (ej: Caja Principal)');
            $table->string('fixed_amount')->default('true')->comment('true o false');
            $table->string('category')->nullable()->comment('Categoría de la caja');
            $table->string('qr_code')->nullable()->comment('Código QR estático');
            $table->string('qr_url')->nullable()->comment('URL del QR estático');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('mercadopago_store_id');
            $table->index('mp_pos_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercadopago_pos');
        Schema::dropIfExists('mercadopago_stores');
    }
};
