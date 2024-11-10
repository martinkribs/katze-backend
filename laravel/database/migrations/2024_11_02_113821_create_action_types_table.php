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
        Schema::create('action_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('usage_limit')->nullable();
            $table->enum('effect', ['kill', 'heal', 'protect', 'reveal', 'block', 'convert', 'other']);
            $table->enum('target_type', ['single', 'multiple', 'self', 'none']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_types');
    }
};
