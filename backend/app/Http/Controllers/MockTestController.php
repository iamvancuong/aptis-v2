<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\Quiz;
use App\Models\Set;
use App\Services\GradingService;
use App\Jobs\ProcessWritingGrading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MockTestController extends Controller
{
    public function __construct(
        private GradingService $gradingService
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
        $sectionConfig = config("aptis.exam_sections.{$skill}");
        $duration = config("aptis.exam_duration.{$skill}");

        // Pick random sets for each section, ensuring no duplicates for repeated parts
        // For Writing & Speaking, we MUST use a single cohesive Set across all parts to maintain the scenario context.
        if ($skill === 'writing' || $skill === 'speaking') {
            $request->validate([
                'set_id' => 'required|exists:sets,id',
            ]);

            // Pick the selected cohesive Set
            $cohesiveSet = Set::where('id', $request->set_id)
                ->where('is_public', true)
                ->first();

            if (!$cohesiveSet) {
                return back()->with('error', "Không đủ bộ đề hoàn chỉnh. Vui lòng liên hệ admin.");
            }

            foreach ($sectionConfig as $part) {
                $sections[] = [
                    'part' => $part,
                    'set_id' => $cohesiveSet->id,
                ];
            }
        } else {
            // For Reading and Listening, pick random sets for each section.
            // NOTE: exam_part_counts defines how many QUESTIONS to show per part (not sets).
            //       getSectionsWithSets() handles the per-part question limit via crc32 deterministic slice.
            //       So we always pick exactly 1 set per part; only Reading Part 2 uses set_ids (multiple sets).
            $usedSetIds = [];
            foreach ($sectionConfig as $part) {
                // Number of SETS to pick: Reading P2 uses 2 sets; everything else = 1 set
                $setCount = ($skill === 'reading' && $part == 2) ? 2 : 1;

                $sets = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                    $q->where('skill', $skill)->where('part', $part);
                })
                    ->where('is_public', true)
                    ->whereNotIn('id', $usedSetIds)
                    ->inRandomOrder()
                    ->limit($setCount)
                    ->get();

                if ($sets->isEmpty()) {
                    return back()->with('error', "Không đủ bộ đề cho Part {$part}. Vui lòng liên hệ admin.");
                }

                $usedSetIds = array_merge($usedSetIds, $sets->pluck('id')->toArray());

                if ($setCount > 1) {
                    $sections[] = [
                        'part' => $part,
                        'set_ids' => $sets->pluck('id')->toArray(),
                    ];
                } else {
                    $sections[] = [
                        'part' => $part,
                        'set_id' => $sets->first()->id,
                    ];
                }
            }
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

        $sectionsWithSets = $mockTest->getSectionsWithSets();

        // Pre-build JSON-safe data for Alpine.js (can't use closures in @json)
        $sectionsJson = $sectionsWithSets->map(function ($s) use ($mockTest) {
            $questions = $s['set']->questions;
            if ($mockTest->skill === 'writing' || $mockTest->skill === 'speaking') {
                $questions = $questions->filter(fn($q) => $q->part === (int)$s['part']);
            }

            return [
                'index' => $s['index'],
                'part' => $s['part'],
                'set_id' => $s['set_id'],
                'questions' => $questions->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'skill' => $q->skill,
                        'part' => $q->part,
                        'stem' => $q->stem,
                        'metadata' => $q->metadata,
                        'point' => $q->point,
                        'title' => $q->title,
                    ];
                })->values(),
            ];
        })->values();

        return view('mock-test.show', compact('mockTest', 'sectionsWithSets', 'sectionsJson'));
    }

    /**
     * Submit all sections at once.
     */
    public function submit(Request $request, MockTest $mockTest)
    {
        // Security: only owner
        if ($mockTest->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Already completed?
        if ($mockTest->status === 'completed') {
            return response()->json([
                'success' => true,
                'redirect' => route('mock-test.result', $mockTest),
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
            if ($mockTest->skill === 'writing' || $mockTest->skill === 'speaking') {
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
                            $path = $file->store('speaking_attempts', 'public');
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
            $remainingCredits = $user->getRemainingWritingAiCredits();
            
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
                        $user->recordWritingAiUsage($aa->question->part);
                        $remainingCredits--;
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

        // Update mock test
        $mockTest->update([
            'finished_at' => $finishedAt,
            'duration_seconds' => $durationSeconds,
            'score' => round($overallScore, 2),
            'section_scores' => $sectionScores,
            'status' => 'completed',
        ]);

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
