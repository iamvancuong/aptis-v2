<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full Test: một lượt thi liên tục 5 phần (Speaking → Listening → Grammar →
 * Reading → Writing). Mỗi phần là một `mock_tests` bình thường gắn `full_test_id`,
 * nên trang thi / đồng hồ / chấm điểm của thi thử từng kỹ năng được dùng lại.
 *
 * `users.full_test_quota`: số lượt Full Test của tài khoản (mặc định 3, admin tăng).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('full_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('in_progress'); // in_progress | completed
            $table->unsignedTinyInteger('current_stage')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('mock_tests', function (Blueprint $table) {
            $table->foreignId('full_test_id')->nullable()->after('user_id')
                ->constrained('full_tests')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('full_test_quota')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_test_quota');
        });

        Schema::table('mock_tests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('full_test_id');
        });

        Schema::dropIfExists('full_tests');
    }
};
