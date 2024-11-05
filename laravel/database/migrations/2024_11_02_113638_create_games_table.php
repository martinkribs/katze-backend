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
            $table->json('role_configuration'); // Stores the number of each role type
            $table->integer('min_players');
            $table->integer('max_players');
            $table->integer('day_duration_minutes')->default(10);
            $table->integer('night_duration_minutes')->default(5);
            $table->boolean('is_private')->default(false);
            $table->string('join_code')->nullable()->unique();
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'cancelled'])->default('waiting');
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
