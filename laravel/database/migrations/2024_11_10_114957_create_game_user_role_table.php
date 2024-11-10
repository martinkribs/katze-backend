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
        Schema::create('game_user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->enum('connection_status', ['invited', 'joined', 'left', 'kicked'])->default('invited');
            $table->enum('user_status', ['alive', 'dead', 'in_love'])->default('alive');
            $table->foreignId('affected_user')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('is_game_master')->default(false);
            $table->timestamps();

            // Ensure unique combination of game and user
            $table->unique(['game_id', 'user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_user_role');
    }
};
