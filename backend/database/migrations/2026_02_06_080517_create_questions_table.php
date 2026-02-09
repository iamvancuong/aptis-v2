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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->enum('skill', ['reading', 'listening', 'writing']);
            $table->tinyInteger('part');
            $table->string('type');
            $table->text('stem')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('image_path')->nullable();
            $table->integer('point')->default(1);
            $table->integer('order')->default(0);
            $table->json('metadata');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
