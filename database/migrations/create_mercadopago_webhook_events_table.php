<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->nullable()->constrained('mercadopago_accounts')->nullOnDelete();

            $table->string('mp_resource_id')->nullable();
            $table->string('topic')->nullable();

            $table->longText('raw_payload')->nullable();
            $table->boolean('signature_valid')->default(false);

            $table->string('status')->default('pending');
            $table->string('error')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_webhook_events');
    }
};
