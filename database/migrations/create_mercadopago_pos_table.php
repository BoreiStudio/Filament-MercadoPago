<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_pos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('mercadopago_stores')->nullOnDelete();

            $table->string('mp_pos_id')->nullable()->unique();
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->boolean('fixed_amount')->default(false);
            $table->string('category')->nullable();

            $table->string('qr_image_url')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_pos');
    }
};
