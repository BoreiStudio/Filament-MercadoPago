<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();

            $table->string('mp_store_id')->nullable()->unique();
            $table->string('name');
            $table->string('external_id')->nullable();

            $table->json('business_hours')->nullable();
            $table->json('location')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_stores');
    }
};
