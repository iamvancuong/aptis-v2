<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bộ lọc "sắp thi trong N ngày" ở màn chọn thành viên lớp.
 *
 * Ca quan trọng nhất là ca cuối: nút "thêm tất cả kết quả lọc" phải đọc CÙNG
 * một truy vấn với màn hiển thị. Hai chỗ lệch nhau thì admin bấm, thấy báo
 * thành công, và chỉ phát hiện khi có người lạ trong lớp.
 */
class ClassGroupCandidateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'ad' . random_int(1, 99999) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'admin', 'status' => 'active',
            'source' => User::SOURCE_MANUAL, 'max_devices' => 3, 'violation_count' => 0,
        ]);
    }

    private function hocVien(string $ten, ?string $ngayThi, string $source = User::SOURCE_IMPORT): User
    {
        return User::create([
            'name' => $ten, 'email' => strtolower(str_replace(' ', '', $ten)) . '@example.test',
            'password' => bcrypt('x'), 'role' => 'user', 'source' => $source,
            'status' => 'active', 'max_devices' => 3, 'violation_count' => 0,
            'expires_at' => $ngayThi,
        ]);
    }

    private function duLieuMau(): ClassGroup
    {
        $this->hocVien('Thi Tuan Nay', now()->addDays(3));
        $this->hocVien('Thi Thang Sau', now()->addDays(40));
        $this->hocVien('Khong Co Ngay Thi', null);

        return ClassGroup::create(['name' => 'Nhóm thi tuần này', 'is_active' => true]);
    }

    public function test_loc_ung_vien_theo_ngay_thi_sap_toi(): void
    {
        $lop = $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', [$lop, 'sap_thi' => 7]))
            ->assertOk()
            ->assertSee('Thi Tuan Nay')
            ->assertDontSee('Thi Thang Sau')
            ->assertDontSee('Khong Co Ngay Thi');
    }

    public function test_khong_loc_thi_hien_het(): void
    {
        $lop = $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', $lop))
            ->assertOk()
            ->assertSee('Thi Tuan Nay')
            ->assertSee('Thi Thang Sau')
            ->assertSee('Khong Co Ngay Thi');
    }

    public function test_them_tat_ca_phai_ap_dung_dung_bo_loc_ngay_thi(): void
    {
        // Nếu ca này đỏ thì nút "thêm tất cả" đang thêm nhiều hơn màn hình hiện,
        // và không có gì báo cho admin biết.
        $lop = $this->duLieuMau();

        $this->actingAs($this->admin())
            ->post(route('admin.class-groups.members.add-all', $lop), ['sap_thi' => 7])
            ->assertRedirect();

        $ten = $lop->members()->pluck('name');

        $this->assertSame(['Thi Tuan Nay'], $ten->all());
    }

    public function test_them_tat_ca_ap_ca_bo_loc_nguon_mac_dinh_cua_lop(): void
    {
        // Bug đã tồn tại: `addAllMatching` không lấy `source` mặc định của lớp,
        // nên lớp có `source_filter` thì màn hiển thị lọc còn nút thêm CẢ TRƯỜNG.
        $lop = ClassGroup::create([
            'name' => 'Lớp mua web', 'is_active' => true, 'source_filter' => User::SOURCE_PURCHASE,
        ]);

        $this->hocVien('Mua Web', now()->addDays(3), User::SOURCE_PURCHASE);
        $this->hocVien('Du Lieu Cu', now()->addDays(3), User::SOURCE_IMPORT);

        $this->actingAs($this->admin())
            ->post(route('admin.class-groups.members.add-all', $lop), [])
            ->assertRedirect();

        $this->assertSame(['Mua Web'], $lop->members()->pluck('name')->all());
    }

    public function test_canh_bao_khi_lop_dang_tu_gom_theo_ngay_thi(): void
    {
        $lop = ClassGroup::create([
            'name' => 'Nhóm thi tuần này', 'is_active' => true, 'auto_exam_days' => 7,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', $lop))
            ->assertOk()
            ->assertSee('đang TỰ GOM theo ngày thi')
            ->assertSee('sẽ bị gỡ ở lần cập nhật sau');
    }

    public function test_lop_thuong_khong_hien_canh_bao_tu_gom(): void
    {
        $lop = $this->duLieuMau();

        $this->actingAs($this->admin())
            ->get(route('admin.class-groups.members', $lop))
            ->assertOk()
            ->assertDontSee('đang TỰ GOM theo ngày thi');
    }
}
