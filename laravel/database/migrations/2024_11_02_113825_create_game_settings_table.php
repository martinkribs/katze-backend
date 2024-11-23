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
        if (!Schema::hasTable('game_settings')) {
            Schema::create('game_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('game_id')->constrained()->onDelete('cascade');
                $table->json('role_configuration');
                $table->boolean('use_default')->default(true);
                $table->timestamps();

                // Each game can only have one settings record
                $table->unique('game_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_settings');
    }
};
