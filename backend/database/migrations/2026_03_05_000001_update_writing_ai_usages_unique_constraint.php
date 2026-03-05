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
        Schema::table('writing_ai_usages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'writing_part']);
            $table->unique(['user_id', 'writing_part', 'reset_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('writing_ai_usages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'writing_part', 'reset_version']);
            $table->unique(['user_id', 'writing_part']);
        });
    }
};
