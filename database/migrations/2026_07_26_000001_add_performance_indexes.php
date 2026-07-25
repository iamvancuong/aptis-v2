<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index cho các bảng bận nhất (attempts/mock_tests/orders). Các cột này hay dùng
 * trong where/orderBy ở History, Dashboard, Leaderboard, Report, Revenue nhưng
 * chưa có index → full-scan khi dữ liệu lớn trên MySQL. Chỉ thêm index, không
 * đổi dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            // Dashboard/History: lọc theo user rồi sắp theo thời gian.
            $table->index(['user_id', 'finished_at'], 'attempts_user_finished_idx');
            // History: lọc theo user + kỹ năng.
            $table->index(['user_id', 'skill'], 'attempts_user_skill_idx');
            // Admin: hàng đợi chấm bài.
            $table->index('is_grading_requested', 'attempts_grading_idx');
        });

        Schema::table('mock_tests', function (Blueprint $table) {
            // Leaderboard: where skill + status='completed' + orderBy score.
            $table->index(['skill', 'status', 'score'], 'mock_tests_leaderboard_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Revenue: aggregate + lọc khoảng ngày theo paid_at.
            $table->index('paid_at', 'orders_paid_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropIndex('attempts_user_finished_idx');
            $table->dropIndex('attempts_user_skill_idx');
            $table->dropIndex('attempts_grading_idx');
        });

        Schema::table('mock_tests', function (Blueprint $table) {
            $table->dropIndex('mock_tests_leaderboard_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_paid_at_idx');
        });
    }
};
