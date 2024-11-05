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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_instance_id')->constrained('game_instances');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->nullable()->constrained('roles');
            $table->boolean('is_alive')->default(true);
            $table->boolean('is_game_master')->default(false);
            $table->string('special_status')->nullable();
            $table->json('additional_info')->nullable(); // For storing player-specific game state
            $table->timestamps();

            // Prevent duplicate players in the same game instance
            $table->unique(['game_instance_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
