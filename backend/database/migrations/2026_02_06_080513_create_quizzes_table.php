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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('skill', ['reading', 'listening', 'writing']);
            $table->tinyInteger('part');
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['skill', 'part']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
