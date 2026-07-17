<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained('mercadopago_payments')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();

            $table->bigInteger('mp_refund_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('approved');

            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_refunds');
    }
};
