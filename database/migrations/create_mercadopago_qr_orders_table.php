<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_qr_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();
            $table->foreignId('pos_id')->constrained('mercadopago_pos')->cascadeOnDelete();

            $table->string('mp_order_id')->nullable()->unique();
            $table->string('external_reference')->nullable()->index();

            $table->string('title')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('items')->nullable();

            $table->string('status')->default('opened');
            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_qr_orders');
    }
};
