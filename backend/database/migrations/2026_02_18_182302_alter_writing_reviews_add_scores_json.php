<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('writing_reviews', function (Blueprint $table) {
            // Replace single score with JSON scores + total_score
            $table->json('scores')->nullable()->after('reviewer_id');
            $table->renameColumn('score', 'total_score');
        });
    }

    public function down(): void
    {
        Schema::table('writing_reviews', function (Blueprint $table) {
            $table->dropColumn('scores');
            $table->renameColumn('total_score', 'score');
        });
    }
};
