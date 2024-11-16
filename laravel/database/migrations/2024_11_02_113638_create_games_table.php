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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->string('timezone', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('role_configuration');
            $table->integer('min_players');
            $table->boolean('is_private')->default(false);
            $table->boolean('is_day');
            $table->string('join_code')->nullable()->unique();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('winning_team', ['villagers', 'cats', 'serial_killer','lovers'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
