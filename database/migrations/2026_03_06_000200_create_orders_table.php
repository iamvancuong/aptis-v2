<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đơn hàng — nguồn sự thật duy nhất cho cả đăng ký tài khoản, thanh toán chấm
 * bài, và màn Doanh số. Một đơn = một lần thanh toán.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Mã đơn dạng SỐ, đúng chuẩn `orderCode` của PayOS (duy nhất).
            $table->unsignedBigInteger('order_code')->unique();

            $table->string('email')->index();

            // registration = mua gói tài khoản · grading = trả phí chấm bài.
            $table->string('type')->default('registration')->index();

            // Với đăng ký: 'week' | 'month'. Với chấm bài: null.
            $table->string('package')->nullable();
            $table->unsignedInteger('quantity')->default(1);

            $table->unsignedBigInteger('amount'); // VND

            // pending → paid | canceled | expired
            $table->string('status')->default('pending')->index();

            // Gắn tài khoản sau khi fulfill (đăng ký) hoặc chủ bài (chấm bài).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('payos_link_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Dữ liệu phụ: ngày thứ 7 buổi hướng dẫn, id bài cần chấm, kỹ năng…
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
