<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\Quiz;
use App\Models\Set;
use App\Services\GradingService;
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
        foreach (array_count_values($sections) as $part => $needed) {
            $available = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                $q->where('skill', $skill)->where('part', $part);
            })->where('is_public', true)->count();

            $partCounts[$part] = [
                'needed' => $needed,
                'available' => $available,
                'enough' => $available >= $needed,
            ];
        }

        $canStart = collect($partCounts)->every(fn($p) => $p['enough']);

        return view('mock-test.lobby', compact('skill', 'sections', 'duration', 'partCounts', 'canStart'));
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
        $usedSetIds = [];
        $sections = [];

        foreach ($sectionConfig as $part) {
            $set = Set::whereHas('quiz', function ($q) use ($skill, $part) {
                $q->where('skill', $skill)->where('part', $part);
            })
                ->where('is_public', true)
                ->whereNotIn('id', $usedSetIds)
                ->inRandomOrder()
                ->first();

            if (!$set) {
                return back()->with('error', "Không đủ bộ đề cho Part {$part}. Vui lòng liên hệ admin.");
            }

            $usedSetIds[] = $set->id;
            $sections[] = [
                'part' => $part,
                'set_id' => $set->id,
            ];
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
        $sectionsJson = $sectionsWithSets->map(function ($s) {
            return [
                'index' => $s['index'],
                'part' => $s['part'],
                'set_id' => $s['set_id'],
                'questions' => $s['set']->questions->map(function ($q) {
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

        $finishedAt = now();
        $durationSeconds = $mockTest->started_at->diffInSeconds($finishedAt);

        foreach ($sectionsWithSets as $sectionIndex => $section) {
            $set = $section['set'];
            $questions = $set->questions;
            $sectionAnswers = $data['answers'][$sectionIndex] ?? [];

            // Grade this section using shared GradingService
            $result = $this->gradingService->gradeSet($questions, $sectionAnswers, 'mock_test');

            // Create an attempt for this section
            $attempt = \App\Models\Attempt::create([
                'user_id' => auth()->id(),
                'skill' => $mockTest->skill,
                'mode' => 'mock_test',
                'set_id' => $set->id,
                'mock_test_id' => $mockTest->id,
                'started_at' => $mockTest->started_at,
                'finished_at' => $finishedAt,
                'duration_seconds' => $durationSeconds,
                'score' => $result['percentage'],
            ]);

            $attempt->attemptAnswers()->createMany($result['attempt_answers']);

            $sectionScores[] = round($result['percentage'], 2);
            $totalEarned += $result['total_earned'];
            $totalPossible += $result['total_possible'];
        }

        // Calculate overall score
        $overallScore = ($totalPossible > 0) ? ($totalEarned / $totalPossible) * 100 : 0;

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
