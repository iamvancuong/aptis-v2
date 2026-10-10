<?php

namespace App\Support;

/**
 * Lời chúc mừng / chia buồn + động viên trên trang kết quả Full Test.
 *
 * Viết tay sẵn (không gọi AI lúc chạy): câu nào hiện ra cũng đã được duyệt
 * giọng văn, hiện tức thì, không tốn lượt OpenAI và không thể hỏng vì mạng.
 *
 * Mỗi tình huống có vài câu; chọn theo mã lượt thi ($seed) nên cùng một bài
 * xem lại vẫn ra đúng câu đó (không "nhảy" lời mỗi lần tải trang), còn các
 * lượt thi khác nhau thì đổi giọng cho đỡ nhàm.
 *
 * Tình huống:
 *   exceeded — vượt mục tiêu
 *   reached  — chạm đúng mục tiêu
 *   close    — chưa đạt nhưng chỉ còn ≤ CLOSE_GAP điểm
 *   far      — chưa đạt, còn xa hơn
 */
class FullTestCaption
{
    /** Còn thiếu từ ngần này điểm trở xuống thì coi là "sát nút". */
    public const CLOSE_GAP = 15;

    private const LINES = [
        'exceeded' => [
            ['{ten_oi}bạn đã vượt cả mục tiêu {target}!',
             'Trình độ ước tính {overall} với {total}/200 điểm — cao hơn cả điều bạn tự đặt ra. Đây là lúc nâng mục tiêu lên một bậc, vì rõ ràng bạn làm được nhiều hơn mình nghĩ.'],
            ['Không chỉ chạm, mà là vượt — {overall} rồi đấy!',
             'Bạn đặt {target}, kết quả trả về {overall} với {total}/200 điểm. Những buổi ôn luyện tưởng chừng lặng lẽ hoá ra đã tích luỹ đủ để bạn đi xa hơn dự tính.'],
            ['Vượt mục tiêu {target}. Quá xuất sắc{ten_phay}!',
             '{total}/200 điểm, trình độ ước tính {overall}. Giữ nhịp này đến ngày thi thật, và đừng ngại đặt cho mình một đích đến xa hơn.'],
        ],
        'reached' => [
            ['Chúc mừng{ten_cach}, bạn đã chạm mục tiêu {target}!',
             'Trình độ ước tính {overall} với {total}/200 điểm. Từng buổi luyện đều đã được đền đáp — việc còn lại là giữ vững phong độ này đến ngày thi thật.'],
            ['{target} — đúng như bạn đã đặt ra.',
             '{total}/200 điểm, đủ chuẩn {overall}. Bạn đã chứng minh mục tiêu này nằm trong tầm tay; ôn đều tay để kết quả thật cũng đẹp như hôm nay nhé.'],
            ['Mục tiêu {target}: đã hoàn thành.',
             'Kết quả {total}/200 điểm cho thấy bạn đã sẵn sàng ở mức {overall}. Tự thưởng cho mình một chút, rồi tiếp tục giữ nhịp nhé.'],
        ],
        'close' => [
            ['Chỉ còn {gap} điểm nữa thôi{ten_a}.',
             'Lần này bạn dừng ở {overall}, sát nút mục tiêu {target}. Một bước hụt không xoá đi quãng đường bạn đã đi — nó chỉ cho biết còn đúng một đoạn ngắn. Tập trung vào {weak} là đoạn ngắn ấy sẽ rút lại rất nhanh.'],
            ['Suýt nữa thôi — và lần sau sẽ là lần đó.',
             '{total}/200 điểm, trình độ {overall}, còn {gap} điểm nữa là chạm {target}. Thành công chưa đến không có nghĩa là bạn chưa đủ giỏi, chỉ là cần thêm một chút thời gian. Ưu tiên {weak} trong vài buổi tới nhé.'],
            ['Gần lắm rồi, đừng dừng lại ở đây.',
             'Khoảng cách tới {target} chỉ còn {gap} điểm. Cảm giác hụt hẫng lúc này ai đi thi cũng từng trải qua, nhưng chính những lần như thế mới cho ta biết chính xác cần sửa gì — lần này là {weak}.'],
        ],
        'far' => [
            ['Bạn đã cố gắng rồi, và nỗ lực đó không mất đi đâu cả.',
             'Lần này bạn ở {overall} với {total}/200 điểm, còn {gap} điểm nữa mới tới {target}. Thành công chưa đến không có nghĩa là bạn chưa đủ — nó chỉ cần thêm thời gian. Bắt đầu từ {weak}: cải thiện chỗ yếu nhất luôn là cách nhanh nhất để kéo tổng điểm lên.'],
            ['Chưa phải lúc này, nhưng chắc chắn không phải mãi mãi.',
             'Kết quả {overall} chưa như mong đợi, và buồn một chút cũng không sao. Hãy coi bài thi này là tấm bản đồ: nó cho thấy bạn đang đứng ở đâu và còn {gap} điểm nữa để tới {target}. Bước đầu tiên: luyện {weak}.'],
            ['Một bài thi không định nghĩa con người bạn.',
             'Hôm nay là {overall} với {total}/200 điểm. Điều đáng ghi nhận là bạn đã ngồi đủ 5 phần, đối mặt với đề như thi thật. Còn {gap} điểm tới {target} — mỗi lượt luyện {weak} từ giờ đều đang lấp dần khoảng trống ấy.'],
        ],
    ];

    /**
     * @param  array  $aim     FullTestService::aim()
     * @param  array  $report  FullTestService::report()
     * @return array{state: string, title: string, body: string}
     */
    public static function for(array $aim, array $report, string $name, int $seed): array
    {
        $state = $aim['exceeded'] ? 'exceeded'
            : ($aim['reached'] ? 'reached'
            : ($aim['gap'] <= self::CLOSE_GAP ? 'close' : 'far'));

        $pool = self::LINES[$state];
        [$title, $body] = $pool[abs($seed) % count($pool)];

        $name = trim($name);
        $weak = $aim['weakest'] ?? null;
        $vars = [
            '{ten_oi}'   => $name !== '' ? "$name ơi, " : '',
            '{ten_phay}' => $name !== '' ? ", $name" : '',
            '{ten_cach}' => $name !== '' ? " $name" : '',
            '{ten_a}'    => $name !== '' ? ", $name ạ" : '',
            '{target}'   => $aim['target'],
            '{overall}'  => $aim['overall'],
            '{total}'    => $report['total'],
            '{gap}'      => $aim['gap'],
            '{weak}'     => $weak ? "{$weak['label']} (đang {$weak['scale']}/50)" : 'kỹ năng yếu nhất',
        ];

        return [
            'state' => $state,
            'title' => self::ucfirst(strtr($title, $vars)),
            'body'  => strtr($body, $vars),
        ];
    }

    private static function ucfirst(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }
}
