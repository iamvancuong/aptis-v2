<?php

namespace App\Services;

use App\Jobs\ProcessSpeakingGrading;
use App\Jobs\ProcessWritingGrading;
use App\Models\AttemptAnswer;
use App\Models\FullTest;
use App\Models\MockTest;
use App\Models\User;
use App\Support\AptisScale;
use App\Support\SpeakingAudio;
use Illuminate\Support\Facades\DB;

/**
 * Điều phối Full Test: 5 phần thi liên tục, mỗi phần là một MockTest bình
 * thường gắn `full_test_id`.
 *
 * Luật:
 *  - Mỗi tài khoản có `users.full_test_quota` lượt (mặc định 3, admin tăng).
 *    Bấm "Bắt đầu" là tính một lượt — bỏ dở cũng tính, để không ai mở đề xem
 *    trước rồi bỏ.
 *  - Mỗi lúc chỉ một lượt đang làm; vào lại thì làm tiếp đúng phần đang dở.
 *  - Phần sau chỉ mở khi phần trước đã nộp; không quay lại phần đã nộp.
 *  - Chấm AI Writing/Speaking trong Full Test KHÔNG trừ lượt AI thường của học
 *    viên — số lượt Full Test đã giới hạn chi phí.
 */
class FullTestService
{
    public function __construct(private MockTestBuilder $builder) {}

    public const SKILL_LABELS = [
        'speaking'  => 'Speaking',
        'listening' => 'Listening',
        'grammar'   => 'Grammar and vocabulary',
        'reading'   => 'Reading',
        'writing'   => 'Writing',
    ];

    /** Trạng thái chấm xem như đã có kết quả cuối. */
    private const FINAL_STATUSES = ['ai_graded', 'graded', 'manually_graded'];

    // ─── Lượt thi ────────────────────────────────────────────────────────────

    public function quotaFor(User $user): int
    {
        return (int) ($user->full_test_quota ?? config('aptis.full_test.default_quota', 3));
    }

    public function usedBy(User $user): int
    {
        return FullTest::where('user_id', $user->id)->count();
    }

    public function remainingFor(User $user): int
    {
        return max(0, $this->quotaFor($user) - $this->usedBy($user));
    }

    public function activeFor(User $user): ?FullTest
    {
        return FullTest::where('user_id', $user->id)->where('status', 'in_progress')->latest('id')->first();
    }

    /**
     * Bắt đầu lượt mới (hoặc trả về lượt đang dở).
     *
     * @return FullTest|string  lượt thi, hoặc thông báo lỗi
     */
    public function start(User $user): FullTest|string
    {
        return DB::transaction(function () use ($user) {
            // Khoá dòng user: bấm "Bắt đầu" hai lần liền không được tạo hai lượt.
            User::whereKey($user->id)->lockForUpdate()->first();

            if ($active = $this->activeFor($user)) {
                return $active;
            }

            if ($this->remainingFor($user) <= 0) {
                return 'Bạn đã dùng hết lượt Full Test. Liên hệ admin để được cấp thêm lượt.';
            }

            // Kiểm tra đủ đề cho cả 5 phần TRƯỚC khi tính lượt.
            foreach (FullTest::stages() as $skill) {
                if (is_string($err = $this->builder->sectionsFor($skill))) {
                    return 'Hệ thống chưa đủ đề cho phần ' . self::SKILL_LABELS[$skill] . '. Vui lòng liên hệ admin.';
                }
            }

            return FullTest::create([
                'user_id'       => $user->id,
                'status'        => 'in_progress',
                'current_stage' => 0,
                'started_at'    => now(),
            ]);
        });
    }

    /**
     * Vào phần đang tới lượt: trả về bài thi đang dở hoặc tạo bài mới.
     *
     * @return MockTest|string
     */
    public function enterStage(FullTest $fullTest): MockTest|string
    {
        return DB::transaction(function () use ($fullTest) {
            $fullTest = FullTest::whereKey($fullTest->id)->lockForUpdate()->first();

            if ($fullTest->isCompleted() || ! ($skill = $fullTest->currentSkill())) {
                return 'Lượt Full Test này đã hoàn thành.';
            }

            $existing = MockTest::where('full_test_id', $fullTest->id)->where('skill', $skill)->first();
            if ($existing) {
                return $existing;
            }

            $sections = $this->builder->sectionsFor($skill);
            if (is_string($sections)) {
                return $sections;
            }

            return MockTest::create([
                'user_id'          => $fullTest->user_id,
                'full_test_id'     => $fullTest->id,
                'skill'            => $skill,
                'sections'         => $sections,
                'duration_minutes' => (int) config("aptis.exam_duration.{$skill}"),
                'started_at'       => now(),
                'status'           => 'in_progress',
            ]);
        });
    }

    /**
     * Gọi sau khi một phần đã nộp: chuyển sang phần kế, hoặc kết thúc lượt.
     */
    public function stageSubmitted(MockTest $mockTest): ?FullTest
    {
        if (! $mockTest->full_test_id) {
            return null;
        }

        return DB::transaction(function () use ($mockTest) {
            $fullTest = FullTest::whereKey($mockTest->full_test_id)->lockForUpdate()->first();
            if (! $fullTest || $fullTest->isCompleted()) {
                return $fullTest;
            }

            $index = array_search($mockTest->skill, FullTest::stages(), true);

            // Chỉ tiến khi đúng là phần hiện tại vừa nộp (nộp trùng không nhảy 2 phần).
            if ($index !== false && $index === $fullTest->current_stage) {
                $next = $index + 1;

                $fullTest->update($next >= count(FullTest::stages())
                    ? ['current_stage' => $next, 'status' => 'completed', 'finished_at' => now()]
                    : ['current_stage' => $next]);
            }

            return $fullTest;
        });
    }

    /**
     * Số giây còn lại của một phần, tính từ lúc vào phần (server giữ giờ — tải
     * lại trang hay xoá localStorage không được thêm giờ).
     */
    public function remainingSeconds(MockTest $mockTest): int
    {
        $total = (int) $mockTest->duration_minutes * 60;
        $elapsed = $mockTest->started_at ? (int) $mockTest->started_at->diffInSeconds(now()) : 0;

        return max(0, $total - $elapsed);
    }

    // ─── Kết quả ────────────────────────────────────────────────────────────

    /**
     * Điểm từng kỹ năng + tổng + bậc tổng.
     *
     * @return array{
     *   skills: array<string, array{skill:string,label:string,submitted:bool,graded:bool,pending:int,failed:int,percent:float,scale:int,level:?string,mock:?MockTest}>,
     *   total: int, overall: string, fully_graded: bool, finished: bool
     * }
     */
    public function report(FullTest $fullTest): array
    {
        $fullTest->loadMissing('mockTests.attempts.attemptAnswers.question');

        $skills = [];
        foreach (FullTest::stages() as $skill) {
            $mock = $fullTest->stageMock($skill);
            $attempt = $mock?->status === 'completed' ? $mock->attempts->first() : null;

            $row = [
                'skill'     => $skill,
                'label'     => self::SKILL_LABELS[$skill] ?? ucfirst($skill),
                'submitted' => (bool) $attempt,
                'graded'    => false,
                'pending'   => 0,
                'failed'    => 0,
                'percent'   => 0.0,
                'scale'     => 0,
                'level'     => null,
                'mock'      => $mock,
            ];

            if ($attempt) {
                if (in_array($skill, ['writing', 'speaking'], true)) {
                    // Tự tính từ điểm từng phần (0–10), phần bỏ trống = 0. KHÔNG dùng
                    // attempts.score: số đó chỉ tính trên các phần đã có điểm, nên bài
                    // bỏ trống 3/4 phần vẫn có thể ra điểm cao.
                    $answers = $attempt->attemptAnswers;
                    $sum = 0.0;
                    foreach ($answers as $a) {
                        if ($this->isBlankAnswer($skill, $a->answer)) {
                            continue;
                        }
                        if (in_array($a->grading_status, self::FINAL_STATUSES, true)) {
                            $sum += (float) ($a->score ?? 0);
                        } elseif ($a->grading_status === 'ai_failed') {
                            $row['failed']++;
                        } else {
                            $row['pending']++;
                        }
                    }
                    $row['percent'] = $answers->count() > 0 ? round($sum / $answers->count() * 10, 2) : 0.0;
                    $row['graded'] = $row['pending'] === 0 && $row['failed'] === 0;
                } else {
                    $row['percent'] = (float) $attempt->score;
                    $row['graded'] = true;
                }

                $row['scale'] = AptisScale::fromPercent($row['percent']);
                if (in_array($skill, AptisScale::CEFR_SKILLS, true)) {
                    $row['level'] = AptisScale::levelForScale($skill, $row['scale']);
                }
            }

            $skills[$skill] = $row;
        }

        $total = 0;
        foreach (AptisScale::CEFR_SKILLS as $skill) {
            $total += $skills[$skill]['scale'] ?? 0;
        }

        $finished = $fullTest->isCompleted();

        return [
            'skills'       => $skills,
            'total'        => $total,
            'overall'      => AptisScale::overallLevel($total),
            'finished'     => $finished,
            'fully_graded' => $finished && collect($skills)->every(fn ($r) => $r['submitted'] && $r['graded']),
        ];
    }

    /**
     * So kết quả với mục tiêu (`users.target_level`) để chúc mừng / động viên.
     * Null nếu chưa chấm xong hoặc học viên chưa đặt mục tiêu.
     *
     * @return array{target:string, overall:string, reached:bool, exceeded:bool, gap:int, weakest:?array}|null
     */
    public function aim(User $user, array $report): ?array
    {
        $target = AptisScale::normalizeLevel($user->target_level);

        if (! $report['fully_graded'] || ! $target) {
            return null;
        }

        $overall = $report['overall'];
        $reached = AptisScale::rank($overall) >= AptisScale::rank($target);

        // Kỹ năng yếu nhất (so theo bậc rồi theo điểm) — gợi ý nên luyện gì trước.
        $weakest = collect(AptisScale::CEFR_SKILLS)
            ->map(fn ($k) => $report['skills'][$k])
            ->sortBy(fn ($r) => [AptisScale::rank((string) $r['level']), $r['scale']])
            ->first();

        return [
            'target'   => $target,
            'overall'  => $overall,
            'reached'  => $reached,
            'exceeded' => AptisScale::rank($overall) > AptisScale::rank($target),
            'gap'      => max(0, AptisScale::overallThreshold($target) - $report['total']),
            'weakest'  => $weakest,
        ];
    }

    /** Phần bỏ trống — không có gì để chấm, tính 0 điểm và không phải chờ. */
    public function isBlankAnswer(string $skill, mixed $raw): bool
    {
        if ($skill === 'speaking') {
            return ! SpeakingAudio::hasRecording($raw);
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
        }

        if (is_array($raw)) {
            $text = '';
            array_walk_recursive($raw, function ($v) use (&$text) {
                $text .= is_scalar($v) ? (string) $v : '';
            });

            return trim($text) === '';
        }

        return trim((string) $raw) === '';
    }

    /**
     * Admin: đẩy lại chấm AI cho các phần còn treo (pending lâu / AI lỗi).
     * Trả về số phần đã đẩy.
     */
    public function retryPendingGrading(FullTest $fullTest): int
    {
        $count = 0;

        foreach (['writing', 'speaking'] as $skill) {
            $attempt = $fullTest->stageMock($skill)?->attempts()->with('attemptAnswers.question')->first();
            if (! $attempt) {
                continue;
            }

            foreach ($attempt->attemptAnswers as $answer) {
                /** @var AttemptAnswer $answer */
                if (! $answer->question
                    || in_array($answer->grading_status, self::FINAL_STATUSES, true)
                    || $this->isBlankAnswer($skill, $answer->answer)) {
                    continue;
                }

                $answer->update(['grading_status' => 'pending']);

                if ($skill === 'writing') {
                    ProcessWritingGrading::dispatch($answer->id, [
                        'part'       => $answer->question->part,
                        'word_limit' => $answer->question->metadata['word_limit'] ?? null,
                        'stem'       => $answer->question->stem,
                    ]);
                } else {
                    ProcessSpeakingGrading::dispatch($answer->id, [
                        'part'     => (int) $answer->question->part,
                        'stem'     => $answer->question->stem,
                        'metadata' => $answer->question->metadata ?? [],
                    ])->onQueue('speaking');
                }

                $count++;
            }
        }

        return $count;
    }
}
