<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Chạy các lệnh KIỂM TRA lớp online ngay trên web, thay cho cPanel Terminal.
 *
 * ⚠️ ĐỌC TRƯỚC KHI THÊM LỆNH MỚI VÀO ĐÂY.
 *
 * Chạy lệnh từ trình duyệt là con đường ngắn nhất tới một cửa hậu, nên trang này
 * dựng theo ba luật cứng — phá luật nào cũng biến nó thành lỗ hổng:
 *
 *  1. **Trình duyệt KHÔNG BAO GIỜ gửi tên lệnh lên.** Nó chỉ gửi một KHOÁ
 *     (`diagnose`, `whois`…). Tên lệnh và tham số nằm cứng trong mảng dưới đây.
 *     Nhận tên lệnh từ request — dù có lọc — là mở đường chạy lệnh tuỳ ý.
 *  2. **Chỉ lệnh CHỈ-ĐỌC.** Không lệnh nào ở đây được ghi vào DB, gửi email, hay
 *     xoá file. Lệnh có `--dry-run` thì cờ đó nằm cứng trong mảng, không phải
 *     tuỳ chọn của người bấm. `classes:remind` KHÔNG được vào đây: nó gửi email
 *     thật cho hàng trăm học viên.
 *  3. **Tham số của người dùng phải qua validate và có kiểu rõ ràng** — email và
 *     số ID buổi. Đặc biệt KHÔNG nhận đường dẫn file: `classes:whois --file=`
 *     đọc được file bất kỳ trên server, nên bản web chỉ nhận văn bản dán vào rồi
 *     tự tách email ra.
 */
class ClassToolsController extends Controller
{
    /**
     * Danh sách trắng. Khoá → lệnh + tham số cố định.
     *
     * `can_nhap` khai ô nhập thêm mà giao diện hiện cho lệnh đó; controller vẫn
     * là nơi quyết định tham số cuối cùng, không phải form.
     */
    private const CONG_CU = [
        'diagnose' => [
            'ten'    => 'Chẩn đoán buổi học',
            'mo_ta'  => 'Từng buổi đang vướng cửa nào: giờ mở, link phòng, lớp, số thành viên. Dùng để đối chiếu link với Google Calendar.',
            'lenh'   => 'classes:diagnose',
            'tham_so' => [],
            'can_nhap' => 'user',
        ],
        'generate' => [
            'ten'    => 'Xem buổi sẽ tự sinh',
            'mo_ta'  => 'Các buổi của lịch lặp hằng tuần sẽ được tạo cho 4 tuần tới. Chạy thử — không tạo gì.',
            'lenh'   => 'classes:generate-sessions',
            'tham_so' => ['--dry-run' => true],
            'can_nhap' => null,
        ],
        'exam' => [
            'ten'    => 'Xem nhóm thi sẽ gồm ai',
            'mo_ta'  => 'Ai sẽ được thêm/gỡ khỏi lớp tự gom theo ngày thi. Chạy thử — không đổi thành viên.',
            'lenh'   => 'classes:sync-exam-groups',
            'tham_so' => ['--dry-run' => true],
            'can_nhap' => null,
        ],
        'scaffold' => [
            'ten'    => 'Xem lịch mẫu sẽ dựng gì',
            'mo_ta'  => 'Đối chiếu file lịch với lớp/buổi đang có. Chạy thử — không tạo gì.',
            'lenh'   => 'classes:scaffold',
            'tham_so' => ['--dry-run' => true],
            'can_nhap' => null,
        ],
        'whois' => [
            'ten'    => 'Gmail này là ai?',
            'mo_ta'  => 'Dán danh sách Gmail (hoặc nguyên file CSV điểm danh Google) — hệ thống tự tách email và cho biết ai là học viên thật, ai vào nhầm lớp, ai là người lạ.',
            'lenh'   => 'classes:whois',
            'tham_so' => [],
            'can_nhap' => 'emails',
        ],
    ];

    /** Trần số địa chỉ tra một lần — tránh một cú dán làm treo request. */
    private const TOI_DA_EMAIL = 1000;

    public function index()
    {
        return view('admin.class-tools.index', [
            'congCu' => self::CONG_CU,
            'ketQua' => null,
            'daChay' => null,
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'cong_cu' => 'required|string|in:' . implode(',', array_keys(self::CONG_CU)),
            'emails'  => 'nullable|string|max:100000',
            'session' => 'nullable|integer|min:1',
            'user'    => 'nullable|email',
        ], [
            'cong_cu.in' => 'Công cụ không hợp lệ.',
            'user.email' => 'Phải là một địa chỉ email.',
        ]);

        $cong = self::CONG_CU[$data['cong_cu']];
        $thamSo = $cong['tham_so'];

        // Tham số của người dùng ghép vào ĐÂY, sau khi đã validate — không lấy
        // thẳng từ request vào lệnh.
        if ($cong['can_nhap'] === 'emails') {
            $email = $this->tachEmail((string) ($data['emails'] ?? ''));

            if ($email === []) {
                return back()->withInput()->withErrors([
                    'emails' => 'Không tìm thấy địa chỉ email nào trong nội dung đã dán.',
                ]);
            }

            $thamSo['email'] = $email;

            if (! empty($data['session'])) {
                $thamSo['--session'] = (int) $data['session'];
            }
        }

        if ($cong['can_nhap'] === 'user' && ! empty($data['user'])) {
            $thamSo['--user'] = $data['user'];
        }

        // `Artisan::call` chứ KHÔNG phải shell: không có chuỗi lệnh nào được ghép,
        // nên không có chỗ cho chèn lệnh. (Host cũng đã tắt `shell_exec`.)
        Artisan::call($cong['lenh'], $thamSo);

        return view('admin.class-tools.index', [
            'congCu' => self::CONG_CU,
            'ketQua' => Artisan::output(),
            'daChay' => $cong['ten'],
        ]);
    }

    /**
     * Tách email khỏi văn bản dán vào — dán nguyên CSV điểm danh cũng được.
     *
     * Bản web cố ý KHÔNG nhận đường dẫn file như bản dòng lệnh: `--file` đọc
     * được file bất kỳ trên server, mà trang này chỉ cần đọc thứ admin dán vào.
     *
     * @return list<string>
     */
    private function tachEmail(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $khop);

        return array_slice(array_values(array_unique($khop[0])), 0, self::TOI_DA_EMAIL);
    }
}
