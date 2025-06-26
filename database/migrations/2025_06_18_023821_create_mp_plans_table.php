<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mp_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('external_id')->nullable()->unique();
            $table->string('status')->default('inactive');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ARS');
            $table->integer('frequency')->default(1);
            $table->string('frequency_type')->default('months');
            $table->integer('repetitions')->default(0);
            $table->json('payment_methods')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_plans');
    }
};
