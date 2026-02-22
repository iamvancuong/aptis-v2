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
        Schema::create('writing_ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('writing_part'); // 1, 2, 3, 4
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('reset_version')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'writing_part']);
            $table->index('user_id');
            $table->index('writing_part');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('writing_ai_usages');
    }
};
