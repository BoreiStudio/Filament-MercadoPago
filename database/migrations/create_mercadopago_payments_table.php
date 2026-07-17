<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained('mercadopago_accounts')->cascadeOnDelete();

            $table->bigInteger('mp_payment_id')->nullable()->unique();
            $table->string('preference_id')->nullable();

            $table->string('status')->default('pending');
            $table->string('status_detail')->nullable();

            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->string('currency_id')->default('ARS');

            $table->string('payment_type_id')->nullable();
            $table->string('payment_method_id')->nullable();

            $table->string('payer_email')->nullable();
            $table->string('external_reference')->nullable()->index();

            $table->string('source')->default('checkout_pro');

            $table->json('raw_payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_payments');
    }
};
