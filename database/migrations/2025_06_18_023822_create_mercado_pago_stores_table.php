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
        Schema::create('mercado_pago_stores', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique(); // ID de MP
            $table->string('name');

            // Datos de ubicación
            $table->string('street_name')->nullable();
            $table->string('street_number')->nullable();
            $table->string('city_name')->nullable();
            $table->string('state_name')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Horarios comerciales como JSON
            $table->json('business_hours')->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_stores');
    }
};
