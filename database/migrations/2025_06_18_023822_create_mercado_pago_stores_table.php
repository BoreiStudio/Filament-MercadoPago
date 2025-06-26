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
            $table->unsignedBigInteger('user_id')->index(); // para manejar multiusuario
            $table->string('external_id')->unique(); // ID de MP
            $table->string('name');
            $table->string('location')->nullable(); // podés expandir esto a una tabla relacionada si necesitás más info
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
