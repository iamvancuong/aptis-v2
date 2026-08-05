<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * "Meet báo Gmail này tiềm ẩn rủi ro — nó là ai?"
 *
 * Milaedu và Google là hai danh tính TÁCH RỜI (§23): trong phòng Meet chỉ nhìn
 * thấy địa chỉ Gmail, không có cách nào biết đó là học viên đã trả tiền hay
 * người lạ. Lệnh này là cầu nối duy nhất giữa hai danh sách đó, và nó tra theo
 * CẢ `email` lẫn `google_email` — người khai Gmail riêng để vào Meet sẽ không
 * bao giờ khớp nếu chỉ tra một cột.
 *
 * Nhận vào BẤT KỲ đoạn text nào rồi tự rút email ra: dán thẳng danh sách người
 * tham gia copy từ Meet, hay nguyên file CSV điểm danh, đều được. Bắt admin dọn
 * tay danh sách trước khi tra là cách chắc chắn nhất để lệnh này không được dùng
 * vào lúc cần nhất — giữa buổi dạy.
 */
class WhoIsGmail extends Command
{
    protected $signature = 'classes:whois
                            {email?* : Email cần tra. Bỏ trống thì dùng --file}
                            {--file= : File chứa danh sách — dán được cả CSV điểm danh, tự tách email}
                            {--session= : ID buổi học, để kiểm luôn từng người CÓ ĐƯỢC VÀO buổi đó không}';

    protected $description = 'Tra ngược Gmail → tài khoản Milaedu (ai là học viên thật, ai là người lạ)';

    public function handle(): int
    {
        $diaChi = $this->gomDiaChi();

        if ($diaChi === []) {
            $this->error('Không tìm thấy địa chỉ email nào trong dữ liệu vào.');
            $this->line('Ví dụ: <info>php artisan classes:whois a@gmail.com b@gmail.com</info>');
            $this->line('       <info>php artisan classes:whois --file=diemdanh.csv --session=12</info>');

            return self::FAILURE;
        }

        $buoi = null;
        if ($id = $this->option('session')) {
            $buoi = ClassSession::with('classGroup')->find($id);

            if (! $buoi) {
                $this->error("Không có buổi học nào với ID: {$id}");

                return self::FAILURE;
            }
        }

        $theoDiaChi = $this->traCuu($diaChi);
        $nhom = $this->phanLoai($diaChi, $theoDiaChi, $buoi);

        $this->inKetQua($nhom, $buoi, count($diaChi));

        return self::SUCCESS;
    }

    /**
     * Rút email khỏi mọi thứ được đưa vào (tham số dòng lệnh + nội dung file).
     *
     * Trả về địa chỉ đã viết thường và bỏ trùng, nhưng GIỮ THỨ TỰ xuất hiện để
     * admin đối chiếu được với danh sách gốc đang mở trên màn hình.
     */
    private function gomDiaChi(): array
    {
        $text = implode(' ', $this->argument('email'));

        if ($duongDan = $this->option('file')) {
            if (! is_readable($duongDan)) {
                $this->error("Không đọc được file: {$duongDan}");

                return [];
            }

            $text .= ' ' . file_get_contents($duongDan);
        }

        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $khop);

        return array_values(array_unique(array_map('strtolower', $khop[0])));
    }

    /**
     * Nạp một lượt mọi tài khoản khớp, rồi lập bảng tra địa chỉ → tài khoản.
     *
     * Một tài khoản có thể khớp bằng hai đường (email tài khoản và Gmail đã
     * khai) nên bảng tra ghi cả hai khoá cùng trỏ về một người.
     */
    private function traCuu(array $diaChi): array
    {
        $users = User::query()
            ->with('classGroups:id,name')
            ->where(fn ($q) => $q
                ->whereIn(DB::raw('LOWER(email)'), $diaChi)
                ->orWhereIn(DB::raw('LOWER(google_email)'), $diaChi))
            ->get();

        $bang = [];

        foreach ($users as $u) {
            $bang[strtolower($u->email)] = $u;

            if ($u->google_email) {
                $bang[strtolower($u->google_email)] = $u;
            }
        }

        return $bang;
    }

    /**
     * Chia làm 4 rổ, xếp theo mức cần xử lý gấp.
     *
     * Rổ `sai_lop` chỉ tồn tại khi có --session, và nó là rổ đáng chú ý nhất
     * trong thực tế: học viên THẬT, còn hạn, nhưng không thuộc lớp của buổi này
     * — đúng kiểu "nhóm web lọt vào buổi trừ nhóm web". Gộp họ chung với người
     * lạ sẽ dẫn tới xử lý oan; gộp chung với người hợp lệ thì không ai thấy.
     */
    private function phanLoai(array $diaChi, array $theoDiaChi, ?ClassSession $buoi): array
    {
        $nhom = ['nguoi_la' => [], 'sai_lop' => [], 'khong_hoc_duoc' => [], 'hop_le' => []];

        foreach ($diaChi as $email) {
            $u = $theoDiaChi[$email] ?? null;

            if (! $u) {
                $nhom['nguoi_la'][] = ['email' => $email, 'user' => null];

                continue;
            }

            $dong = ['email' => $email, 'user' => $u];

            if ($u->isBlocked() || $u->isExpired()) {
                $nhom['khong_hoc_duoc'][] = $dong;
            } elseif ($buoi && ! $u->canJoinClassSession($buoi)) {
                $nhom['sai_lop'][] = $dong;
            } else {
                $nhom['hop_le'][] = $dong;
            }
        }

        return $nhom;
    }

    private function inKetQua(array $nhom, ?ClassSession $buoi, int $tong): void
    {
        $this->newLine();

        if ($buoi) {
            $lop = $buoi->classGroup?->name ?? 'không gắn lớp (mở cho mọi học viên còn hạn)';
            $this->line("Đối chiếu với buổi <info>#{$buoi->id} — {$buoi->title}</info> · lớp: <info>{$lop}</info>");
        } else {
            $this->line('<comment>Chưa có --session nên chỉ tra danh tính, KHÔNG kiểm tư cách vào lớp.</comment>');
        }

        $this->line("Đã tra <info>{$tong}</info> địa chỉ.");
        $this->newLine();

        $this->inNhom($nhom['nguoi_la'], '🔴 KHÔNG CÓ TÀI KHOẢN MILAEDU — người lạ',
            'Không khớp cả email đăng ký lẫn Gmail đã khai. Đây là nhóm cần mời ra khỏi phòng.');

        $this->inNhom($nhom['sai_lop'], '🟠 CÓ TÀI KHOẢN nhưng KHÔNG thuộc lớp của buổi này',
            'Học viên thật, còn hạn, nhưng vào nhầm buổi — hoặc danh sách mời Calendar của buổi này quá rộng.');

        $this->inNhom($nhom['khong_hoc_duoc'], '🟡 TÀI KHOẢN HẾT HẠN HOẶC BỊ KHOÁ',
            'Đã hết quyền học mà vẫn vào được phòng ⇒ họ còn giữ lời mời Calendar cũ (lỗ hổng §25②). Gỡ khỏi sự kiện.');

        $this->inNhom($nhom['hop_le'], '✅ HỌC VIÊN HỢP LỆ', null);

        // Dòng tổng kết cố ý KHÔNG tô màu: mã màu chen vào giữa con số và chữ,
        // làm mọi phép tìm chuỗi (kể cả của test) khớp hụt một cách khó hiểu.
        $this->newLine();
        $this->line('━━━ TỔNG KẾT ━━━');
        $this->line(sprintf('  %d người lạ', count($nhom['nguoi_la'])));
        $this->line(sprintf('  %d sai lớp', count($nhom['sai_lop'])));
        $this->line(sprintf('  %d hết hạn/khoá', count($nhom['khong_hoc_duoc'])));
        $this->line(sprintf('  %d hợp lệ', count($nhom['hop_le'])));

        if (! $buoi) {
            $this->line('💡 Thêm <info>--session=ID</info> để biết ai vào nhầm buổi (xem ID ở /admin/class-sessions).');
        }
    }

    private function inNhom(array $dong, string $tieuDe, ?string $giaiThich): void
    {
        if ($dong === []) {
            return;
        }

        $soDong = count($dong);
        $this->line("<comment>━━━ {$tieuDe} ({$soDong}) ━━━</comment>");

        if ($giaiThich) {
            $this->line("    {$giaiThich}");
        }

        // Mỗi người MỘT dòng, cố ý không dùng `table()`: bảng nhiều cột bị Symfony
        // ngắt dòng khi terminal hẹp hơn tổng độ rộng, và Terminal cPanel thì luôn
        // hẹp. Email bị cắt làm đôi là hỏng đúng thứ lệnh này sinh ra để đọc.
        foreach ($dong as $d) {
            $this->line('  ' . $this->moTa($d));
        }

        $this->newLine();
    }

    private function moTa(array $dong): string
    {
        $u = $dong['user'];

        if (! $u) {
            return $dong['email'];
        }

        $phan = [$u->name, $u->sourceLabel()];

        // Gmail khai riêng thì email tài khoản là thông tin KHÁC và cần thiết để
        // tra tiếp ở /admin/users. Trùng nhau (96% trường hợp) thì lặp lại chỉ
        // làm dòng dài thêm.
        if (strtolower($u->email) !== $dong['email']) {
            $phan[] = 'tài khoản: ' . $u->email;
        }

        $phan[] = 'hạn ' . ($u->expires_at?->format('d/m/Y') ?? 'không giới hạn');

        if ($ten = $u->classGroups->pluck('name')->implode(', ')) {
            $phan[] = 'lớp: ' . $ten;
        }

        return $dong['email'] . '  →  ' . implode(' · ', $phan);
    }
}
