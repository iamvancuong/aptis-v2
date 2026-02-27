<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\Quiz;
use App\Models\Set;
use App\Services\GradingService;
use App\Jobs\ProcessWritingGrading;
use Illuminate\Http\Request;

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
        if (!in_array($skill, ['reading', 'listening', 'writing'])) {
            abort(404);
        }

        $sections = config("aptis.exam_sections.{$skill}");
        $duration = config("aptis.exam_duration.{$skill}");

        // Check available sets per part
        $partCounts = [];
        
        $writingSets = collect();
        if ($skill === 'writing') {
            // For writing, we just need at least one cohesive set (Part 1 container)
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
            'skill' => 'required|in:reading,listening,writing',
        ]);

        $skill = $request->skill;
        $sectionConfig = config("aptis.exam_sections.{$skill}");
        $duration = config("aptis.exam_duration.{$skill}");

        // Pick random sets for each section, ensuring no duplicates for repeated parts
        // For Writing, we MUST use a single cohesive Set across all parts to maintain the scenario context.
        if ($skill === 'writing') {
            $request->validate([
                'set_id' => 'required|exists:sets,id',
            ]);

            // Pick the selected cohesive Set
            $cohesiveSet = Set::where('id', $request->set_id)
                ->where('is_public', true)
                ->first();

            if (!$cohesiveSet) {
                return back()->with('error', "Không đủ bộ đề Writing hoàn chỉnh. Vui lòng liên hệ admin.");
            }

            foreach ($sectionConfig as $part) {
                $sections[] = [
                    'part' => $part,
                    'set_id' => $cohesiveSet->id,
                ];
            }
        } else {
            // For Reading and Listening, pick random sets for each section
            $usedSetIds = [];
            foreach ($sectionConfig as $part) {
                $idealCount = config("aptis.exam_part_counts.{$skill}.{$part}", 1);

                $sets = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                    $q->where('skill', $skill)->where('part', $part);
                })
                    ->where('is_public', true)
                    ->whereNotIn('id', $usedSetIds)
                    ->inRandomOrder()
                    ->limit($idealCount)
                    ->get();

                if ($sets->isEmpty()) {
                    return back()->with('error', "Không đủ bộ đề cho Part {$part}. Vui lòng liên hệ admin.");
                }

                $usedSetIds = array_merge($usedSetIds, $sets->pluck('id')->toArray());
                
                if ($idealCount > 1) {
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
            if ($mockTest->skill === 'writing') {
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

        $data = $request->validate([
            'answers' => 'required|array', // answers[section_index][question_id] = answer
        ]);

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
            if ($mockTest->skill === 'writing') {
                $questions = $questions->filter(fn($q) => $q->part === (int)$section['part']);
            }
            $sectionAnswers = $data['answers'][$sectionIndex] ?? [];

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
            'mode' => 'mock_test',
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
            $attempt->load(['attemptAnswers.question']);
            foreach ($attempt->attemptAnswers as $aa) {
                if ($aa->grading_status === 'pending' && $aa->question) {
                    ProcessWritingGrading::dispatch($aa->id, [
                        'part'       => $aa->question->part,
                        'word_limit' => $aa->question->metadata['word_limit'] ?? null,
                        'stem'       => $aa->question->stem,
                    ]);
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
        $attempts = $mockTest->attempts()->with('attemptAnswers')->get();

        return view('mock-test.result', compact('mockTest', 'sectionsWithSets', 'attempts'));
    }
}
