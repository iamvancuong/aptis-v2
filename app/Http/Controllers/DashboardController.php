<?php

namespace App\Http\Controllers;

use App\Models\WritingAiUsage;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // AI limit per part per reset cycle
    const AI_LIMIT_PER_PART = 5;

    public function index()
    {
        $user = auth()->user();

        $baseQuery = \App\Models\Attempt::where('user_id', $user->id)->whereNotNull('score');

        // Tổng số bài & điểm trung bình tính bằng aggregate query (rẻ, dùng index),
        // không kéo toàn bộ rows về PHP.
        $totalAttempts = (clone $baseQuery)->count();
        $avgScore      = $totalAttempts > 0 ? round((clone $baseQuery)->avg('score'), 1) : null;

        // Biểu đồ tiến độ chỉ cần 6 tháng gần nhất, nên chỉ
        // cần dữ liệu gần đây + đúng các cột dùng tới (tránh nạp cả nghìn dòng/đủ cột).
        $attempts = (clone $baseQuery)
            ->where('finished_at', '>=', now()->subMonths(6))
            ->orderBy('finished_at', 'asc')
            ->get(['skill', 'mode', 'score', 'finished_at']);

        // Biểu đồ tiến độ: GOM THEO TUẦN (điểm trung bình mỗi tuần mỗi kỹ năng).
        // Bản cũ vẽ từng ngày → 60+ mốc, đường giật lên xuống và nhãn trục X chồng
        // nhau, học viên nhìn không ra xu hướng. Theo tuần thì tối đa ~26 mốc / 6 tháng.
        // Server trả sẵn nhãn đã sắp xếp + dãy điểm thẳng hàng, JS chỉ việc vẽ
        // (bản cũ sắp ngày "dd/mm" trong JS với năm cố định nên sai khi qua năm mới).
        $seriesKeys = ['speaking', 'listening', 'grammar', 'reading', 'writing', 'mock_test'];
        $weeks = [];
        foreach ($attempts as $attempt) {
            if (!$attempt->finished_at) continue;

            $weekKey  = $attempt->finished_at->copy()->startOfWeek()->format('Y-m-d');
            $skillKey = in_array($attempt->mode, ['mock', 'mock_test']) ? 'mock_test' : $attempt->skill;
            if (!in_array($skillKey, $seriesKeys, true)) continue;

            $weeks[$weekKey][$skillKey][] = (float) $attempt->score;
        }
        ksort($weeks);

        $statisticsData = ['labels' => [], 'series' => array_fill_keys($seriesKeys, [])];
        foreach ($weeks as $weekKey => $bySkill) {
            $statisticsData['labels'][] = \Carbon\Carbon::parse($weekKey)->format('d/m');
            foreach ($seriesKeys as $key) {
                $statisticsData['series'][$key][] = isset($bySkill[$key])
                    ? round(array_sum($bySkill[$key]) / count($bySkill[$key]), 1)
                    : null;
            }
        }
        // Bỏ dãy rỗng để chú thích không hiện kỹ năng chưa làm lần nào.
        $statisticsData['series'] = array_filter(
            $statisticsData['series'],
            fn ($points) => count(array_filter($points, fn ($p) => $p !== null)) > 0
        );

        // Quick Stats ($totalAttempts, $avgScore) đã tính bằng aggregate query ở đầu.

        // AI usage: check against current reset_version (overall usage)
        $aiRemaining = $user->getRemainingWritingAiCredits();
        
        $defaultAiLimit = (int)(\App\Models\Setting::where('key', 'default_ai_limit')->value('value') ?? 10);
        $totalAiLimit = $user->isAdmin() ? -1 : ($defaultAiLimit + ($user->ai_extra_uses ?? 0));


        // Account expiry
        $expiresAt          = $user->expires_at;
        $daysUntilExpiry    = $user->daysUntilExpiration();
        $expirationStatus   = $user->expirationStatus();

        // Unseen graded attempts
        $unseenWriting = \App\Models\Attempt::where('user_id', $user->id)
            ->where('skill', 'writing')
            ->where('is_seen', false)
            ->whereNotNull('score')
            ->count();

        $unseenSpeaking = \App\Models\Attempt::where('user_id', $user->id)
            ->where('skill', 'speaking')
            ->where('is_seen', false)
            ->whereNotNull('score')
            ->count();

        // Buổi học online gần nhất còn hiệu lực (đang diễn ra hoặc sắp tới).
        // Phải lọc theo lớp — nếu không, học viên thấy thẻ "lớp sắp tới" của lớp
        // không phải của mình, bấm vào thì bị cổng join chặn: khó hiểu và khó chịu.
        //
        // Tính năng đang hoãn → trả null luôn, khỏi tốn query. Blade đã bọc
        // `@if($nextClass ?? null)` nên thẻ tự biến mất.
        $nextClass = config('aptis.classes_enabled')
            ? \App\Models\ClassSession::visibleToStudents()
                ->allowedFor(auth()->user())
                ->first()
            : null;

        // Thẻ "Ôn từ vựng hôm nay". Chỉ hiện khi học viên đã có từ trong sổ tay —
        // sổ tay trống thì thẻ chỉ là thêm một lời nhắc vô nghĩa.
        $vocabToday = null;
        if (config('aptis.vocab.enabled')) {
            $vocabTotal = \App\Models\VocabularyItem::where('user_id', $user->id)->count();
            if ($vocabTotal > 0) {
                $vocabToday = [
                    'total' => $vocabTotal,
                    'due' => \App\Models\VocabularyItem::where('user_id', $user->id)->due()->count(),
                    'reviewed' => \App\Models\VocabDailyStat::reviewedToday($user->id),
                    'streak' => \App\Models\VocabDailyStat::streak($user->id),
                ];
            }
        }

        // Dòng trạng thái dưới mỗi ô kỹ năng: điểm lần làm gần nhất + số bài đang
        // chờ chấm (Writing/Speaking). 2 câu gom nhóm, không N+1.
        $lastIds = \App\Models\Attempt::where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->selectRaw('skill, max(id) as last_id')
            ->groupBy('skill')
            ->pluck('last_id', 'skill');
        $lastScores = \App\Models\Attempt::whereIn('id', $lastIds->values())
            ->pluck('score', 'skill');
        $dangCham = \App\Models\Attempt::where('user_id', $user->id)
            ->whereIn('skill', ['writing', 'speaking'])
            ->whereHas('attemptAnswers', fn ($q) => $q->where('grading_status', 'pending'))
            ->selectRaw('skill, count(*) as n')
            ->groupBy('skill')
            ->pluck('n', 'skill');

        $skillStatus = [];
        foreach (\App\Support\SkillMeta::ORDER as $skill) {
            if (($dangCham[$skill] ?? 0) > 0) {
                $skillStatus[$skill] = ['text' => 'Đang chấm ' . $dangCham[$skill] . ' bài', 'tone' => 'amber'];
            } elseif ($lastIds->has($skill)) {
                $diem = $lastScores[$skill] ?? null;
                $skillStatus[$skill] = [
                    'text' => $diem !== null ? 'Lần gần nhất ' . round((float) $diem) . '%' : 'Đã làm, chờ điểm',
                    'tone' => 'gray',
                ];
            } else {
                $skillStatus[$skill] = ['text' => 'Chưa làm', 'tone' => 'muted'];
            }
        }

        $fullTestRemaining = app(\App\Services\FullTestService::class)->remainingFor($user);

        return view('dashboard', compact(
            'skillStatus',
            'fullTestRemaining',
            'vocabToday',
            'nextClass',
            'statisticsData',
            'totalAttempts',
            'avgScore',
            'aiRemaining',
            'totalAiLimit',
            'expiresAt',
            'daysUntilExpiry',
            'expirationStatus',
            'unseenWriting',
            'unseenSpeaking'
        ));
    }
}
