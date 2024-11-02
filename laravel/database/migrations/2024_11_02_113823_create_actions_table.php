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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds');
            $table->foreignId('executing_player_id')->constrained('players');
            $table->foreignId('target_player_id')->constrained('players');
            $table->foreignId('action_type_id')->constrained('action_types');
            $table->foreignId('result_type_id')->nullable()->constrained('result_types');
            $table->text('action_notes')->nullable();
            $table->boolean('is_successful')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};