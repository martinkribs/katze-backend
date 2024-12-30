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
        if (!Schema::hasTable('action_types')) {
            Schema::create('action_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description');
                $table->integer('usage_limit')->nullable();
                $table->foreignId('result_type_id')->constrained('result_types');
                $table->enum('target_type', ['single', 'multiple', 'self', 'none']);
                $table->json('allowed_phases')->default('["day"]');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_types');
    }
};
