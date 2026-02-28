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
        Schema::table('attempts', function (Blueprint $table) {
            $table->boolean('is_grading_requested')->default(false)->after('metadata');
            $table->timestamp('grading_requested_at')->nullable()->after('is_grading_requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn(['is_grading_requested', 'grading_requested_at']);
        });
    }
};
