<?php

/**
 * THĂM DÒ CHẤM NÓI BẰNG AI — Giai đoạn 1 + 2 (xem PLAN_CHAM_SPEAKING_AI.md mục 5).
 *
 * ĐÂY KHÔNG PHẢI CODE PRODUCTION. Script rời, chạy tay, KHÔNG được gọi từ route/
 * job/cron nào. Nó CHỈ ĐỌC: không sửa AttemptAnswer, không ghi điểm, không trừ credit.
 *
 *   php scripts/speaking_ai_probe.php [số_bài]      (mặc định 10)
 *
 * Trả lời 3 câu của cổng dừng:
 *   GĐ1  shared host có gọi ra api.openai.com được không · audio chiếm bao nhiêu ổ đĩa
 *   GĐ1  độ dài audio THẬT là bao nhiêu → chốt lại chi phí (plan đang giả định 1 phút/bài)
 *   GĐ2  phiên âm có chính xác với giọng Việt nói tiếng Anh không → cô Dung nghe & chấm
 *
 * Báo cáo ra file HTML có sẵn trình phát audio + transcript cạnh nhau để cô Dung
 * vừa nghe vừa đọc. CỐ Ý KHÔNG chấm điểm: GĐ3 yêu cầu cô Dung chấm mù trước.
 *
 * ⚠️ Script gửi audio giọng học viên lên OpenAI (rủi ro 5 trong plan).
 * ⚠️ Báo cáo nằm trong thư mục web công khai → XOÁ SAU KHI XONG (lệnh in ở cuối).
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const TRANSCRIBE_MODEL = 'gpt-4o-mini-transcribe';   // $0.003/phút — rẻ nhất (plan mục 2)
const CALIBRATION_MODEL = 'whisper-1';               // chỉ dùng 1 lần: verbose_json trả về `duration`
const PRICE_PER_MINUTE = 0.003;
const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;           // giới hạn 25MB/file của API
const USD_TO_VND = 26000;

$sampleSize = (int) ($argv[1] ?? 10);
$line = str_repeat('=', 64);

function out(string $s = ''): void
{
    echo $s . PHP_EOL;
}

function human(int $bytes): string
{
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

$apiKey = config('services.openai.key');

out($line);
out(' THĂM DÒ CHẤM NÓI BẰNG AI — GĐ1 (kỹ thuật) + GĐ2 (chất lượng phiên âm)');
out($line);

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 1 — GĐ1: host có gọi ra ngoài được không
 * Cổng dừng: khác 200 → shared host chặn outbound → phải đổi kiến trúc trước
 * khi bàn tiếp. Biết ở đây rẻ hơn nhiều so với biết sau khi đã code xong job.
 * ────────────────────────────────────────────────────────────────────────── */
out();
out('BƯỚC 1 — Kết nối tới api.openai.com');

if (empty($apiKey)) {
    out('  ✗ DỪNG: .env chưa có OPENAI_API_KEY. Không thăm dò được gì thêm.');
    exit(1);
}
out('  · OPENAI_API_KEY: có (' . substr($apiKey, 0, 7) . '…' . substr($apiKey, -4) . ')');

try {
    $ping = Http::withToken($apiKey)->timeout(20)->get('https://api.openai.com/v1/models');
    out('  · HTTP ' . $ping->status());
    if (!$ping->successful()) {
        out('  ✗ DỪNG: gọi ra ngoài thất bại. Body: ' . mb_substr($ping->body(), 0, 300));
        out('    → Host chặn outbound hoặc key sai. Xem cổng dừng GĐ1 trong plan.');
        exit(1);
    }
    out('  ✓ Gọi ra ngoài OK — cổng GĐ1 (kết nối) đã qua.');
} catch (\Throwable $e) {
    out('  ✗ DỪNG: ' . $e->getMessage());
    out('    → Nếu là lỗi SSL/CA thì là vấn đề cấu hình host, không phải code.');
    exit(1);
}

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 2 — GĐ1: audio đang chiếm bao nhiêu ổ đĩa (rủi ro 3: 30GB chia 21 web)
 * ────────────────────────────────────────────────────────────────────────── */
out();
out('BƯỚC 2 — Dung lượng audio đã lưu');

$disk = Storage::disk('public');
$allFiles = $disk->exists('speaking_attempts') ? $disk->files('speaking_attempts') : [];
$totalBytes = 0;
$sizes = [];
foreach ($allFiles as $f) {
    $s = $disk->size($f);
    $totalBytes += $s;
    $sizes[] = $s;
}
sort($sizes);

out('  · Số file      : ' . count($allFiles));
out('  · Tổng dung lượng: ' . human($totalBytes));
if ($sizes) {
    out('  · Nhỏ nhất / trung vị / lớn nhất: '
        . human($sizes[0]) . ' / '
        . human($sizes[intdiv(count($sizes), 2)]) . ' / '
        . human($sizes[count($sizes) - 1]));
}
if (!$allFiles) {
    out('  ✗ DỪNG: không có file audio nào ở đây. Máy local trống — chạy script này trên cPanel.');
    exit(1);
}

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 3 — chọn mẫu từ bài THẬT của học viên (mới nhất trước)
 * Đi từ DB chứ không quét thư mục: file trong thư mục có thể mồ côi, còn cái ta
 * cần đo là audio thật sự gắn với một câu trả lời.
 * ────────────────────────────────────────────────────────────────────────── */
out();
out("BƯỚC 3 — Lấy {$sampleSize} bài Nói mới nhất từ DB");

$rows = DB::table('attempt_answers')
    ->join('attempts', 'attempts.id', '=', 'attempt_answers.attempt_id')
    ->where('attempts.skill', 'speaking')
    ->whereNotNull('attempt_answers.answer')
    ->orderByDesc('attempt_answers.id')
    ->limit($sampleSize * 3)          // dư ra vì một số bản ghi trỏ tới file đã mất
    ->get([
        'attempt_answers.id as answer_id',
        'attempt_answers.answer',
        'attempt_answers.question_id',
        'attempts.id as attempt_id',
        'attempts.created_at',
    ]);

$samples = [];
foreach ($rows as $row) {
    if (count($samples) >= $sampleSize) break;

    $decoded = json_decode($row->answer, true);
    $paths = is_array($decoded) ? $decoded : [$row->answer];

    foreach ($paths as $path) {
        if (count($samples) >= $sampleSize) break;
        if (!is_string($path) || !str_contains($path, 'speaking_attempts/')) continue;
        if (!$disk->exists($path)) continue;

        $samples[] = [
            'answer_id'   => $row->answer_id,
            'attempt_id'  => $row->attempt_id,
            'question_id' => $row->question_id,
            'created_at'  => $row->created_at,
            'path'        => $path,
            'bytes'       => $disk->size($path),
        ];
    }
}

out('  · Lấy được ' . count($samples) . ' file audio còn tồn tại trên đĩa.');
if (!$samples) {
    out('  ✗ DỪNG: bản ghi DB không trỏ tới file nào còn tồn tại.');
    exit(1);
}

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 4 — đo độ dài THẬT của 1 file để hiệu chỉnh chi phí
 * Plan đang giả định "1 phút/bài" và tự đánh dấu là CHƯA KIỂM CHỨNG. whisper-1
 * với verbose_json trả về `duration` chính xác; gọi đúng 1 lần rồi suy ra tỉ lệ
 * byte/giây để ước lượng cho các file còn lại mà không tốn thêm tiền.
 * ────────────────────────────────────────────────────────────────────────── */
out();
out('BƯỚC 4 — Đo độ dài thật (hiệu chỉnh giả định "1 phút/bài")');

$bytesPerSecond = null;
$calibrationFile = $samples[0];

try {
    $resp = Http::withToken($apiKey)
        ->timeout(180)
        ->attach('file', $disk->get($calibrationFile['path']), basename($calibrationFile['path']))
        ->post('https://api.openai.com/v1/audio/transcriptions', [
            'model'           => CALIBRATION_MODEL,
            'response_format' => 'verbose_json',
        ]);

    if ($resp->successful() && $resp->json('duration')) {
        $duration = (float) $resp->json('duration');
        $bytesPerSecond = $calibrationFile['bytes'] / max($duration, 0.001);
        out('  · File mẫu: ' . human($calibrationFile['bytes']) . ' = ' . round($duration, 1) . ' giây');
        out('  · Tỉ lệ suy ra: ' . round($bytesPerSecond / 1024, 1) . ' KB/giây');
    } else {
        out('  ! Không đo được (HTTP ' . $resp->status() . '). Bỏ qua phần ước tính chi phí.');
    }
} catch (\Throwable $e) {
    out('  ! Không đo được: ' . $e->getMessage());
}

if ($bytesPerSecond) {
    $totalSeconds = $totalBytes / $bytesPerSecond;
    $avgSeconds = ($totalSeconds / max(count($allFiles), 1));
    $costPerFile = ($avgSeconds / 60) * PRICE_PER_MINUTE;

    out('  · Độ dài trung bình 1 bài (ước tính): ' . round($avgSeconds, 1) . ' giây');
    out('  · Chi phí phiên âm 1 bài: ~$' . number_format($costPerFile, 5)
        . ' (~' . number_format($costPerFile * USD_TO_VND) . 'đ)');
    out('  · So với plan (giả định 1 phút = ~$0.005/bài): '
        . ($avgSeconds > 60 ? 'DÀI HƠN → plan đang ước tính THẤP' : 'ngắn hơn → plan ước tính an toàn'));
}

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 5 — GĐ2: phiên âm bằng model sẽ dùng thật
 * Đây là cổng quan trọng nhất. Phiên âm sai thì AI chấm một bài học viên không
 * hề nói — không prompt nào cứu được.
 * ────────────────────────────────────────────────────────────────────────── */
out();
out('BƯỚC 5 — Phiên âm ' . count($samples) . ' bài bằng ' . TRANSCRIBE_MODEL);

$results = [];
foreach ($samples as $i => $s) {
    $label = '  [' . ($i + 1) . '/' . count($samples) . '] answer#' . $s['answer_id'];

    if ($s['bytes'] > MAX_UPLOAD_BYTES) {
        out($label . ' — BỎ QUA: ' . human($s['bytes']) . ' vượt giới hạn 25MB.');
        $results[] = $s + ['transcript' => null, 'error' => 'Vượt 25MB'];
        continue;
    }

    try {
        $resp = Http::withToken($apiKey)
            ->timeout(180)
            ->attach('file', $disk->get($s['path']), basename($s['path']))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => TRANSCRIBE_MODEL,
            ]);

        if (!$resp->successful()) {
            out($label . ' — LỖI HTTP ' . $resp->status() . ': ' . mb_substr($resp->body(), 0, 160));
            $results[] = $s + ['transcript' => null, 'error' => 'HTTP ' . $resp->status()];
            continue;
        }

        $text = trim((string) $resp->json('text'));
        out($label . ' — ' . mb_strlen($text) . ' ký tự: ' . mb_substr($text, 0, 70) . (mb_strlen($text) > 70 ? '…' : ''));
        $results[] = $s + ['transcript' => $text, 'error' => null];
    } catch (\Throwable $e) {
        out($label . ' — LỖI: ' . $e->getMessage());
        $results[] = $s + ['transcript' => null, 'error' => $e->getMessage()];
    }
}

$ok = count(array_filter($results, fn($r) => $r['transcript'] !== null));
$empty = count(array_filter($results, fn($r) => $r['transcript'] === ''));

/* ─────────────────────────────────────────────────────────────────────────────
 * BƯỚC 6 — báo cáo để cô Dung vừa nghe vừa đọc
 * Tên thư mục ngẫu nhiên vì báo cáo nằm trong vùng web công khai và chứa giọng
 * thật của học viên. Xoá sau khi chấm xong.
 * ────────────────────────────────────────────────────────────────────────── */
$slug = '_probe_' . bin2hex(random_bytes(6));
$reportPath = $slug . '/bao-cao.html';

$html = '<meta charset="utf-8"><title>Thăm dò phiên âm bài Nói</title>';
$html .= '<style>body{font:15px/1.6 system-ui,sans-serif;max-width:900px;margin:2rem auto;padding:0 1rem;color:#1e293b}'
    . 'h1{font-size:1.4rem}.card{border:1px solid #cbd5e1;border-radius:8px;padding:1rem;margin:1rem 0}'
    . '.meta{color:#64748b;font-size:.85rem;margin-bottom:.5rem}'
    . '.t{background:#f8fafc;border-left:3px solid #94a3b8;padding:.75rem;white-space:pre-wrap;margin:.5rem 0}'
    . '.err{color:#b91c1c}audio{width:100%;margin:.25rem 0}'
    . '.rate{background:#fefce8;border:1px solid #fde68a;padding:.5rem;border-radius:6px;font-size:.9rem}'
    . 'table{border-collapse:collapse;width:100%}td,th{border:1px solid #cbd5e1;padding:.4rem .6rem;text-align:left}</style>';

$html .= '<h1>Thăm dò phiên âm bài Nói — Giai đoạn 2</h1>';
$html .= '<p>Model phiên âm: <b>' . TRANSCRIBE_MODEL . '</b> · Ngày chạy: ' . now()->format('d/m/Y H:i') . '</p>';
$html .= '<p><b>Cách chấm:</b> với từng bài, <b>bấm nghe audio</b> rồi <b>đọc transcript ngay dưới</b>. '
    . 'Ghi lại: máy phiên âm đúng khoảng bao nhiêu %? Có chỗ nào máy nghe ra một câu <i>hoàn toàn khác</i> '
    . 'với điều học viên nói không?</p>';
$html .= '<p><b>Cổng dừng:</b> nếu transcript sai nhiều thì dừng hẳn hướng phiên âm — '
    . 'AI sẽ chấm một bài học viên không hề nói.</p>';

if ($bytesPerSecond) {
    $html .= '<p>Độ dài trung bình đo được: <b>' . round(($totalBytes / $bytesPerSecond) / max(count($allFiles), 1), 1)
        . ' giây/bài</b> · Tổng audio đang lưu: <b>' . human($totalBytes) . '</b> (' . count($allFiles) . ' file)</p>';
}

foreach ($results as $i => $r) {
    $url = asset('storage/' . $r['path']);
    $html .= '<div class="card">';
    $html .= '<div class="meta">Bài ' . ($i + 1) . ' · answer#' . $r['answer_id']
        . ' · attempt#' . $r['attempt_id']
        . ' · câu hỏi#' . $r['question_id']
        . ' · ' . human($r['bytes'])
        . ' · nộp ' . $r['created_at'] . '</div>';
    $html .= '<audio controls preload="none" src="' . e($url) . '"></audio>';

    if ($r['transcript'] === null) {
        $html .= '<div class="t err">Không phiên âm được: ' . e($r['error']) . '</div>';
    } elseif ($r['transcript'] === '') {
        $html .= '<div class="t err">Transcript RỖNG — máy không nghe ra chữ nào. '
            . 'Nghe thử: bài này có tiếng nói không, hay học viên nộp file im lặng?</div>';
    } else {
        $html .= '<div class="t">' . e($r['transcript']) . '</div>';
    }

    $html .= '<div class="rate">Cô Dung chấm: phiên âm đúng khoảng ______% '
        . '· có câu nào máy nghe sai hẳn nghĩa không? ______</div>';
    $html .= '</div>';
}

$html .= '<h2>Tổng kết để điền vào plan</h2><table>'
    . '<tr><th>Chỉ số</th><th>Kết quả</th></tr>'
    . '<tr><td>Gọi ra api.openai.com</td><td>OK (HTTP 200)</td></tr>'
    . '<tr><td>Số bài phiên âm được</td><td>' . $ok . '/' . count($results) . '</td></tr>'
    . '<tr><td>Transcript rỗng</td><td>' . $empty . '</td></tr>'
    . '<tr><td>Tổng audio đang lưu</td><td>' . human($totalBytes) . ' — ' . count($allFiles) . ' file</td></tr>'
    . '<tr><td>Độ chính xác phiên âm</td><td>___% (cô Dung điền)</td></tr>'
    . '</table>';

$disk->put($reportPath, $html);

out();
out($line);
out(' XONG');
out($line);
out('  · Phiên âm được : ' . $ok . '/' . count($results) . ' bài' . ($empty ? " (trong đó {$empty} bài transcript RỖNG)" : ''));
out('  · Báo cáo       : ' . asset('storage/' . $reportPath));
out();
out('  TIẾP THEO: gửi link trên cho cô Dung — vừa nghe audio vừa đọc transcript,');
out('  chấm phiên âm đúng khoảng bao nhiêu %. Đó là cổng GĐ2 trong plan.');
out();
out('  ⚠️ Báo cáo nằm ở vùng web công khai và chứa giọng học viên. Chấm xong thì xoá:');
out('     rm -rf storage/app/public/' . $slug);
out();
