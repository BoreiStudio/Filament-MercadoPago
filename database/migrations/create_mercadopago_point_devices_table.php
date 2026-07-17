<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_point_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();
            $table->foreignId('pos_id')->nullable()->constrained('mercadopago_pos')->nullOnDelete();

            $table->string('device_id')->unique();
            $table->string('model')->nullable();
            $table->string('operating_mode')->nullable();
            $table->string('status')->default('active');

            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_point_devices');
    }
};
