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
        Schema::create('role_action_type', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained();
            $table->foreignId('action_type_id')->constrained();
            $table->primary(['role_id', 'action_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_action_type');
    }
};
