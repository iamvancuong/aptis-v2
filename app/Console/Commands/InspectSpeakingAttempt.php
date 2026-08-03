<?php

namespace App\Console\Commands;

use App\Models\Attempt;
use App\Models\MockTest;
use App\Support\SpeakingAudio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Xem tình trạng chấm của một bài Nói, KHÔNG sửa gì.
 *
 * Tồn tại vì `php artisan tinker` KHÔNG chạy được trên cPanel của dự án này:
 * host tắt `shell_exec` nên PsySH chết ngay khi khởi động. Mọi lệnh chẩn đoán
 * phải là artisan command thật, không được dựa vào tinker.
 */
class InspectSpeakingAttempt extends Command
{
    protected $signature = 'speaking:inspect
                            {id? : ID Attempt (mặc định: bài Nói mới nhất)}
                            {--mock= : Tra theo ID MockTest trên URL /mock-test/{id}/result}';

    protected $description = 'In tình trạng chấm AI của một bài Nói (chỉ đọc, không sửa)';

    public function handle(): int
    {
        $this->line('── Cấu hình ──');
        $this->line('  OPENAI_API_KEY  : ' . (config('services.openai.key') ? 'có' : '⚠️ THIẾU'));
        $this->line('  Model phiên âm  : ' . config('services.openai.transcribe_model'));
        $this->line('  Chấm Nói bằng AI: ' . (config('services.openai.speaking_ai_enabled', true) ? 'BẬT' : '⚠️ TẮT'));

        $this->line('── Hàng đợi ──');
        try {
            $this->line('  Job đang chờ : ' . DB::table('jobs')->count());
            $this->line('  Job thất bại : ' . DB::table('failed_jobs')->count());
        } catch (\Throwable $e) {
            $this->warn('  Không đọc được bảng jobs: ' . $e->getMessage());
        }

        $attempt = $this->resolveAttempt();

        if (!$attempt) {
            $this->newLine();
            $this->error('Không tìm thấy bài Nói nào.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('── Bài làm ──');
        $this->line("  Attempt #{$attempt->id} · {$attempt->user?->email} · nộp {$attempt->created_at}");
        $this->line('  Điểm bài      : ' . ($attempt->score ?? '—'));
        $this->line('  Đã vào hàng chờ giáo viên: ' . ($attempt->is_grading_requested ? 'có' : 'KHÔNG'));
        $this->newLine();

        foreach ($attempt->attemptAnswers as $answer) {
            $recordings = count(SpeakingAudio::pathsOf($answer->answer));
            $ai = $answer->ai_metadata ?? [];

            $this->line(sprintf(
                '  Part %-3s answer#%-8s %-14s điểm=%-5s bản ghi=%d',
                $answer->question?->part ?? '?',
                $answer->id,
                $answer->grading_status ?? 'null',
                $answer->score ?? '—',
                $recordings
            ));

            if (!empty($ai['error'])) {
                $this->warn("        lỗi: {$ai['error']['reason']} — {$ai['error']['message']}");
            }
            if (!empty($ai['transcript'])) {
                $this->line('        transcript: ' . mb_substr($ai['transcript'], 0, 70) . '…');
            }
            if (!empty($ai['feedback']['cefr_level'])) {
                $this->line('        band: ' . $ai['feedback']['cefr_level']);
            }
        }

        $this->newLine();
        $this->line('Chấm tay bài này: php artisan speaking:grade-attempt ' . $attempt->id);

        return self::SUCCESS;
    }

    private function resolveAttempt(): ?Attempt
    {
        $with = ['attemptAnswers.question', 'user'];

        if ($mockId = $this->option('mock')) {
            // Số trên URL /mock-test/{id}/result là MockTest, không phải Attempt —
            // nhầm chỗ này là lỗi hay gặp nhất khi tra cứu.
            return MockTest::find($mockId)?->attempts()->with($with)->first();
        }

        if ($id = $this->argument('id')) {
            return Attempt::with($with)->find($id);
        }

        return Attempt::with($with)->where('skill', 'speaking')->latest('id')->first();
    }
}
