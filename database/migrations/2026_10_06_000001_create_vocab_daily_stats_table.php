<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Số lượt ôn từ vựng theo ngày của từng học viên — nguồn của "chuỗi ngày học".
 *
 * `vocabulary_items.last_reviewed_at` chỉ giữ lần ôn cuối của từng từ nên không
 * dựng lại được lịch sử theo ngày; cần bảng riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocab_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('reviews')->default(0);
            $table->unsignedInteger('correct')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_daily_stats');
    }
};
