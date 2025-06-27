<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mercado_pago_terminals', function (Blueprint $table) {
            $table->id();

            $table->string('terminal_id')->unique(); // id: "PAX_A910__SMARTPOS1234345545"
            $table->unsignedBigInteger('pos_id')->index(); // pos_id: 47792476
            $table->string('store_id')->index(); // store_id: "47792478"
            $table->string('external_pos_id')->nullable(); // external_pos_id: "SUC0101POS"
            $table->string('operating_mode')->nullable(); // "PDV | STANDALONE | UNDEFINED"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_terminals');
    }
};
