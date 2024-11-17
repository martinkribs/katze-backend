<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('voting_rounds')) {
            Schema::create('voting_rounds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained('games');
                $table->integer('round_number');
                $table->boolean('is_day');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voting_rounds');
    }
};
