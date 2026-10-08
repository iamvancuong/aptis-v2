<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\Quiz;
use App\Models\Set;
use App\Services\GradingService;
use App\Services\QuestionSanitizer;
use App\Services\SpeakingAiDispatcher;
use App\Jobs\ProcessWritingGrading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MockTestController extends Controller
{
    public function __construct(
        private GradingService $gradingService,
        private QuestionSanitizer $sanitizer,
        private SpeakingAiDispatcher $speakingAiDispatcher,
        private \App\Services\MockTestBuilder $builder,
        private \App\Services\FullTestService $fullTests
    ) {}

    /**
     * Lobby page — show exam info for a skill.
     */
    public function create($skill)
    {
        if (!in_array($skill, ['reading', 'listening', 'writing', 'speaking'])) {
            abort(404);
        }

        $sections = config("aptis.exam_sections.{$skill}");
        $duration = config("aptis.exam_duration.{$skill}");

        // Check available sets per part
        $partCounts = [];
        
        $writingSets = collect();
        if ($skill === 'writing' || $skill === 'speaking') {
            // For writing and speaking, we just need at least one cohesive set (Part 1 container)
            $writingSetsQuery = Set::whereHas('quiz', function ($q) use ($skill) {
                $q->where('skill', $skill)->where('part', 1);
            })->where('is_public', true);
            
            $availableSets = $writingSetsQuery->count();
            $writingSets = $writingSetsQuery->get();

            foreach ($sections as $part) {
                $partCounts[$part] = [
                    'needed' => 1,
                    'available' => $availableSets,
                    'enough' => $availableSets >= 1,
                ];
            }
        } else {
            foreach (array_count_values($sections) as $part => $repeats) {
                $perPartCount = config("aptis.exam_part_counts.{$skill}.{$part}", 1);
                $needed = $repeats * $perPartCount;

                $available = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                    $q->where('skill', $skill)->where('part', $part);
                })->where('is_public', true)->count();

                $partCounts[$part] = [
                    'needed' => $needed,
                    'available' => $available,
                    'enough' => $available >= 1, // At least one set required to start
                ];
            }
        }

        $canStart = collect($partCounts)->every(fn($p) => $p['enough']);

        return view('mock-test.lobby', compact('skill', 'sections', 'duration', 'partCounts', 'canStart', 'writingSets'));
    }

    /**
     * Start a mock test — pick random sets for each section.
     */
    public function start(Request $request)
    {
        $request->validate([
            'skill' => 'required|in:reading,listening,writing,speaking',
        ]);

        $skill = $request->skill;
        $duration = config("aptis.exam_duration.{$skill}");

        // Writing & Speaking: học viên chọn MỘT bộ đề liền mạch cho mọi phần.
        $cohesiveSet = null;
        if ($skill === 'writing' || $skill === 'speaking') {
            $request->validate([
                'set_id' => 'required|exists:sets,id',
            ]);

            $cohesiveSet = Set::where('id', $request->set_id)
                ->where('is_public', true)
                ->first();

            if (!$cohesiveSet) {
                return back()->with('error', "Không đủ bộ đề hoàn chỉnh. Vui lòng liên hệ admin.");
            }
        }

        $sections = $this->builder->sectionsFor($skill, $cohesiveSet);
        if (is_string($sections)) {
            return back()->with('error', $sections);
        }

        $mockTest = MockTest::create([
            'user_id' => auth()->id(),
            'skill' => $skill,
            'sections' => $sections,
            'duration_minutes' => $duration,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('mock-test.show', $mockTest);
    }

    /**
     * Exam page — render all sections with timer and tabs.
     */
    public function show(MockTest $mockTest)
    {
        // Security: only owner can view
        if ($mockTest->user_id !== auth()->id()) {
            abort(403);
        }

        // Already completed?
        if ($mockTest->status === 'completed') {
            return redirect()->route('mock-test.result', $mockTest);
        }

        // Phần thi của Full Test: giờ do server giữ (tính từ lúc vào phần), tải lại
        // trang không được cộng thêm giờ. Bài thường giữ cách cũ (đủ giờ + localStorage).
        $fullTest = $mockTest->fullTest;
        $remainingSeconds = $fullTest
            ? $this->fullTests->remainingSeconds($mockTest)
            : (int) $mockTest->duration_minutes * 60;

        $sectionsWithSets = $mockTest->getSectionsWithSets();

        // Pre-build JSON-safe data for Alpine.js (can't use closures in @json)
        $sectionsJson = $sectionsWithSets->map(function ($s) use ($mockTest) {
            $questions = $s['set']->questions;
            if (in_array($mockTest->skill, \App\Services\MockTestBuilder::COHESIVE_SKILLS, true)) {
                $questions = $questions->filter(fn($q) => $q->part === (int)$s['part']);
            }

            return [
                'index' => $s['index'],
                'part' => $s['part'],
                // Nhãn theo đề APTIS thật (Reading: 2→"2-3", 3→"4", 4→"5"). Tính sẵn
                // ở server để giao diện Alpine không phải mang theo bản đồ ánh xạ
                // riêng — hai bản đồ ở hai nơi thì sớm muộn cũng lệch nhau.
                'part_label' => \App\Support\PartLabel::number($mockTest->skill, $s['part']),
                'set_id' => $s['set_id'],
                // Answer keys are stripped here: this is a graded, timed exam,
                // so nothing the learner could score from may reach the browser.
                // Answers are revealed by the result page after submission.
                'questions' => $questions->map(function ($q) use ($mockTest) {
                    $metadata = $this->sanitizer->metadataForClient($q);

                    // Grammar Part 2: xáo danh sách từ (thứ tự gốc có thể trùng thứ tự
                    // đáp án). Xáo cố định theo bài thi để tải lại trang không đổi chỗ.
                    if ($q->skill === 'grammar' && is_array($metadata['dropdown_pool'] ?? null)) {
                        $metadata['dropdown_pool'] = (new \Random\Randomizer(new \Random\Engine\Mt19937(crc32($mockTest->id . '|' . $q->id))))
                            ->shuffleArray(array_values($metadata['dropdown_pool']));
                    }

                    return [
                        'id' => $q->id,
                        'skill' => $q->skill,
                        'part' => $q->part,
                        // Reading: stem Part 4 chứa mẹo nhớ tiếng Việt (gợi ý đáp án) →
                        // trong giờ thi chỉ gửi title, không để lộ qua F12.
                        'stem' => $q->skill === 'reading' ? ($q->title ?: $q->stem) : $q->stem,
                        'audio_path' => $q->audio_path,
                        'audio_url' => $this->sanitizer->audioUrl($q),
                        'audio_urls' => $this->sanitizer->audioUrls($q),
                        'image_path' => $q->image_path,
                        'metadata' => $metadata,
                        'point' => $q->point,
                        'title' => $q->title,
                    ];
                })->values(),
            ];
        })->values();

        $stageIndex = $fullTest ? array_search($mockTest->skill, \App\Models\FullTest::stages(), true) : null;

        return view('mock-test.show', compact('mockTest', 'sectionsWithSets', 'sectionsJson', 'fullTest', 'remainingSeconds', 'stageIndex'));
    }

    /**
     * Submit all sections at once.
     *
     * Khoá theo bài thi: bấm nộp liên tục, hai tab, hay hết giờ tự nộp trùng lúc
     * bấm nộp — chỉ request đầu tiên được chấm. Không khoá thì hai request đến cùng
     * lúc đều thấy `in_progress`, tạo HAI bài làm và đẩy chấm AI HAI lần.
     */
    public function submit(Request $request, MockTest $mockTest)
    {
        if ($mockTest->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Giữ khoá tối đa 5 phút (nộp Speaking tải file lên có thể lâu).
        $lock = \Illuminate\Support\Facades\Cache::lock("mock-test-submit:{$mockTest->id}", 300);

        if (! $lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Bài thi đang được nộp, vui lòng chờ…',
            ], 409);
        }

        try {
            // Đọc lại trạng thái SAU khi có khoá: request trước có thể vừa nộp xong.
            return $this->handleSubmit($request, $mockTest->fresh());
        } finally {
            $lock->release();
        }
    }

    private function handleSubmit(Request $request, MockTest $mockTest)
    {
        // Security: only owner
        if ($mockTest->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Already completed?
        if ($mockTest->status === 'completed') {
            return response()->json([
                'success' => true,
                'redirect' => $mockTest->full_test_id
                    ? route('full-test.show', $mockTest->full_test_id)
                    : route('mock-test.result', $mockTest),
                'message' => 'Bài thi đã được nộp trước đó.',
            ]);
        }

        $rules = [];
        if ($mockTest->skill !== 'speaking') {
            $rules['answers'] = 'required|array';
        }
        $data = $request->validate($rules);

        // DEBUG LOGGING FOR SPEAKING AUDIO
        if ($mockTest->skill === 'speaking') {
            Log::info("=== MOCK TEST SUBMIT: SPEAKING ===");
            $speakingAudioFiles = $request->file('speaking_audio');
             Log::info("Request has 'speaking_audio' files: " . (!empty($speakingAudioFiles) ? 'YES' : 'NO'));
            if (!empty($speakingAudioFiles)) {
                Log::info("speaking_audio count: " . count($speakingAudioFiles));
                foreach ($speakingAudioFiles as $qId => $qFiles) {
                    $qFilesArray = is_array($qFiles) ? $qFiles : [$qFiles];
                    Log::info("  - QID: {$qId}, file count: " . count($qFilesArray));
                }
            } else {
                Log::warning("No speaking_audio files received by backend. Request content type: " . $request->header('Content-Type'));
                Log::info("All request keys: " . implode(', ', array_keys($request->all())));
            }
        }

        $sectionsWithSets = $mockTest->getSectionsWithSets();
        $sectionScores = [];
        $totalEarned = 0;
        $totalPossible = 0;
        
        $allAttemptAnswers = [];
        // For writing, we'll store the cohesive set ID to attach to the single attempt
        $firstSetId = null;

        $finishedAt = now();
        $durationSeconds = $mockTest->started_at->diffInSeconds($finishedAt);
        
        $partStats = [];

        foreach ($sectionsWithSets as $sectionIndex => $section) {
            /** @var array $section */
            $set = $section['set'];
            if (!$firstSetId) $firstSetId = $set->id;
            
            $questions = $set->questions;
            if (in_array($mockTest->skill, \App\Services\MockTestBuilder::COHESIVE_SKILLS, true)) {
                $questions = $questions->filter(fn($q) => $q->part === (int)$section['part']);
            }
            $sectionAnswers = $request->input("answers.{$sectionIndex}") ?? [];

            // Handle Speaking Audio uploads from global speaking_audio field
            $speakingAudio = $request->file('speaking_audio');
            if ($mockTest->skill === 'speaking' && !empty($speakingAudio)) {
                Log::info("--- Speaking: Received global audio files for MockTest ---");
                foreach ($questions as $q) {
                    if (isset($speakingAudio[$q->id])) {
                        $savedPaths = [];
                        $files = is_array($speakingAudio[$q->id]) ? $speakingAudio[$q->id] : [$speakingAudio[$q->id]];
                        
                        foreach ($files as $file) {
                            // Kiểm tra định dạng + ép đuôi file — xem SpeakingAudio::storeUpload.
                            $path = \App\Support\SpeakingAudio::storeUpload($file);
                            if ($path === null) {
                                continue;
                            }
                            $savedPaths[] = $path;
                            Log::info("--- Speaking MockTest: Saved audio for Q{$q->id} ---", ['path' => $path]);
                        }
                        
                        $sectionAnswers[$q->id] = $savedPaths; // Always store array of paths in DB
                    }
                }
            }

            // Grade this section using shared GradingService
            $result = $this->gradingService->gradeSet($questions, $sectionAnswers, 'mock_test');

            // Collect attempt answers for this section
            $allAttemptAnswers = array_merge($allAttemptAnswers, $result['attempt_answers']);

            // Collect part stats for metadata
            $part = (int)$section['part'];
            if (!isset($partStats[$part])) {
                $partStats[$part] = ['correct' => 0, 'total' => count($questions)];
            }
            foreach ($result['attempt_answers'] as $ans) {
                if ($ans['is_correct']) {
                    $partStats[$part]['correct']++;
                }
            }

            $sectionScores[] = round($result['percentage'], 2);
            $totalEarned += $result['total_earned'];
            $totalPossible += $result['total_possible'];
        }

        // Calculate overall score
        $overallScore = ($totalPossible > 0) ? ($totalEarned / $totalPossible) * 100 : 0;

        // Create a SINGLE attempt for the entire mock test skill
        $attempt = \App\Models\Attempt::create([
            'user_id' => auth()->id(),
            'skill' => $mockTest->skill,
            'mode' => 'mock',
            'set_id' => $firstSetId,
            'mock_test_id' => $mockTest->id,
            'started_at' => $mockTest->started_at,
            'finished_at' => $finishedAt,
            'duration_seconds' => $durationSeconds,
            'score' => round($overallScore, 2),
            'metadata' => ['part_stats' => $partStats],
        ]);

        $attempt->attemptAnswers()->createMany($allAttemptAnswers);

        // For writing mock test: dispatch AI grading jobs asynchronously
        if ($mockTest->skill === 'writing') {
            $user = auth()->user();
            // Full Test: chấm AI không trừ lượt AI thường (số lượt Full Test đã giới hạn).
            $isFullTest = (bool) $mockTest->full_test_id;
            $remainingCredits = $isFullTest ? PHP_INT_MAX : $user->getRemainingWritingAiCredits();
            
            $attempt->load(['attemptAnswers.question']);
            foreach ($attempt->attemptAnswers as $aa) {
                if ($aa->grading_status === 'pending' && $aa->question) {
                    if ($remainingCredits > 0) {
                        ProcessWritingGrading::dispatch($aa->id, [
                            'part'       => $aa->question->part,
                            'word_limit' => $aa->question->metadata['word_limit'] ?? null,
                            'stem'       => $aa->question->stem,
                        ]);
                        
                        // Record usage and decrement local counter
                        if (! $isFullTest) {
                            $user->recordWritingAiUsage($aa->question->part);
                            $remainingCredits--;
                        }
                    } else {
                        Log::info('AI Limit reached during Mock Test submission', [
                            'user_id' => $user->id,
                            'attempt_id' => $attempt->id,
                            'answer_id' => $aa->id
                        ]);
                        $aa->update(['grading_status' => 'limit_reached']);
                    }
                }
            }
        }

        // Bài Nói: chấm AI tự động (phiên âm → chấm transcript). Điểm AI là nháp
        // tham khảo, giáo viên chấm tay vẫn ghi đè được.
        if ($mockTest->skill === 'speaking') {
            // ⏸️ TẮT tự gửi giáo viên chấm bài Nói: hiện chỉ để AI chấm nháp, KHÔNG
            // tự bật `is_grading_requested` nên bài không vào hàng chờ giáo viên
            // (`/admin/speaking-reviews` lọc theo cờ này → hàng chờ sẽ trống).
            // Bật lại = mở lại khối cập nhật cờ dưới đây:
            //   if (!$attempt->is_grading_requested) {
            //       $attempt->update(['is_grading_requested' => true, 'grading_requested_at' => now()]);
            //   }
            $this->speakingAiDispatcher->dispatchFor($attempt, auth()->user(), chargeCredits: ! $mockTest->full_test_id);
        }

        // Update mock test
        $mockTest->update([
            'finished_at' => $finishedAt,
            'duration_seconds' => $durationSeconds,
            'score' => round($overallScore, 2),
            'section_scores' => $sectionScores,
            'status' => 'completed',
        ]);

        // Full Test: sang phần kế (hoặc kết thúc) — không mở trang kết quả từng phần.
        if ($mockTest->full_test_id) {
            $this->fullTests->stageSubmitted($mockTest);

            return response()->json([
                'success' => true,
                'redirect' => route('full-test.show', $mockTest->full_test_id),
                'message' => 'Đã nộp phần thi!',
            ]);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('mock-test.result', $mockTest),
            'score' => round($overallScore, 2),
            'message' => 'Nộp bài thành công!',
        ]);
    }

    /**
     * Results page — per-section breakdown.
     */
    public function result(MockTest $mockTest)
    {
        // Security: only owner
        if ($mockTest->user_id !== auth()->id()) {
            abort(403);
        }

        if ($mockTest->status !== 'completed') {
            return redirect()->route('mock-test.show', $mockTest);
        }

        // Phần của Full Test chưa thi xong cả lượt: không xem đáp án từng phần giữa chừng.
        if ($mockTest->full_test_id && ! $mockTest->fullTest?->isCompleted()) {
            return redirect()->route('full-test.show', $mockTest->full_test_id);
        }

        $sectionsWithSets = $mockTest->getSectionsWithSets();

        // Load attempts for this mock test
        $attempts = $mockTest->attempts()->with('attemptAnswers.question')->get();

        // Calculate grading requests count for this skill
        $gradingRequestsCount = \App\Models\Attempt::where('user_id', auth()->id())
            ->where('skill', $mockTest->skill)
            ->where('is_grading_requested', true)
            ->count();

        return view('mock-test.result', compact('mockTest', 'sectionsWithSets', 'attempts', 'gradingRequestsCount'));
    }
}
