<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_accounts', function (Blueprint $table) {
            $table->id();

            $table->nullableMorphs('tenant');

            $table->bigInteger('mp_user_id');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('public_key')->nullable();
            $table->text('scope')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('live_mode')->default(false);
            $table->string('status')->default('disconnected');
            $table->timestamp('last_refreshed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_accounts');
    }
};
