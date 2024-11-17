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
        if (!Schema::hasTable('game_invitations')) {
            Schema::create('game_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->unique()->constrained('games')->onDelete('cascade');
                $table->foreignId('invited_by')->constrained('users');
                $table->uuid('token')->unique();
                $table->enum('status', ['active', 'expired'])->default('active');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_invitations');
    }
};
