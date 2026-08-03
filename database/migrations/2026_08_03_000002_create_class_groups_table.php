<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Lớp" = một nhóm học viên cố định, học nhiều buổi.
 *
 * `source_filter` KHÔNG tham gia kiểm tra quyền — nó chỉ nhớ ý định của lớp
 * ("lớp này dành cho học viên mua qua chuyển khoản") để làm mặc định cho ô lọc
 * ở màn chọn thành viên. Quyền vào buổi chỉ đọc pivot `class_group_user`.
 *
 * Lý do tách hai thứ đó: nếu lớp tự động = "mọi người có source = X" thì admin
 * mất quyền tự chọn, và trường hợp thật "học viên tạo tay nhưng cần vào lớp trả
 * phí" chỉ xử lý được bằng cách sửa `source` — tức là làm hỏng dữ liệu nguồn gốc
 * để lách một luật hiển thị.
 *
 * `meet_link` ở mức LỚP: mỗi lớp một phòng Meet (sự kiện Calendar lặp lại), buổi
 * học kế thừa link của lớp. Buổi vẫn có thể tự đặt link riêng khi cần đổi phòng
 * đột xuất. Xem `ClassSession::effectiveMeetLink()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // 'purchase' | 'manual' | null (không lọc) — chỉ là gợi ý cho màn chọn thành viên.
            $table->string('source_filter', 20)->nullable();
            // Link phòng Meet dùng chung cho mọi buổi của lớp. Không render ra HTML.
            $table->string('meet_link', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('class_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('added_at')->nullable();

            // Một học viên chỉ nằm trong một lớp đúng một lần.
            $table->unique(['class_group_id', 'user_id']);
            // Truy vấn "học viên này thuộc lớp nào" chạy mỗi lần mở /lop-hoc.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_group_user');
        Schema::dropIfExists('class_groups');
    }
};
