<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Set;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class WritingSetController extends Controller
{
    /**
     * Display a listing of Writing Sets.
     */
    public function index()
    {
        // Fetch Sets that belong to Writing Skill 'Part 1' (the designated Cohesive container)
        $sets = Set::whereHas('quiz', function ($query) {
            $query->where('skill', 'writing')
                  ->where('part', 1);
        })->withCount('questions')->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.writing-sets.index', compact('sets'));
    }

    /**
     * Show the form for creating a new Cohesive Writing Set.
     */
    public function create()
    {
        return view('admin.writing-sets.create');
    }

    /**
     * Store a newly created Writing Set along with all 4 Parts.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'is_public' => 'boolean',
            'has_part_1' => 'boolean',
            'has_part_2' => 'boolean',
            
            // Part 1 Validation
            'part1_instructions' => 'nullable|string',
            'part1_f1_label' => 'nullable|string', 'part1_f1_placeholder' => 'nullable|string',
            'part1_f2_label' => 'nullable|string', 'part1_f2_placeholder' => 'nullable|string',
            'part1_f3_label' => 'nullable|string', 'part1_f3_placeholder' => 'nullable|string',
            'part1_f4_label' => 'nullable|string', 'part1_f4_placeholder' => 'nullable|string',
            'part1_f5_label' => 'nullable|string', 'part1_f5_placeholder' => 'nullable|string',
            'part1_sample_answer' => 'nullable|string',
            
            // Part 2 Validation
            'part2_scenario' => 'nullable|string',
            'part2_hints' => 'nullable|string',
            'part2_min' => 'nullable|integer', 'part2_max' => 'nullable|integer',
            'part2_sample_answer' => 'nullable|string',
            
            // Part 3 Validation
            'part3_stem' => 'nullable|string',
            'part3_prompt_1' => 'required|string', 'part3_prompt_2' => 'required|string', 'part3_prompt_3' => 'required|string',
            'part3_min' => 'nullable|integer', 'part3_max' => 'nullable|integer',
            'part3_sample_1' => 'nullable|string', 'part3_sample_2' => 'nullable|string', 'part3_sample_3' => 'nullable|string',
            
            // Part 4 Validation
            'part4_context' => 'required|string',
            'part4_email_greeting' => 'nullable|string',
            'part4_email_body' => 'required|string',
            'part4_email_sign_off' => 'nullable|string',
            'part4_task1_instruction' => 'required|string',
            'part4_task1_min' => 'nullable|integer', 'part4_task1_max' => 'nullable|integer',
            'part4_task1_sample' => 'nullable|string',
            'part4_task2_instruction' => 'required|string',
            'part4_task2_min' => 'nullable|integer', 'part4_task2_max' => 'nullable|integer',
            'part4_task2_sample' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Find the Writing Quiz (Part 1 as the anchor)
            $quiz = Quiz::where('skill', 'writing')->where('part', 1)->firstOrFail();

            // Create Set
            $set = Set::create([
                'quiz_id' => $quiz->id,
                'title' => $validated['title'],
                'is_public' => $request->has('is_public'),
                'order' => Set::where('quiz_id', $quiz->id)->max('order') + 1,
            ]);

            // Create Part 1 Question if enabled
            if ($request->has('has_part_1')) {
                $this->createPart1($set, $quiz, $validated);
            }
            // Create Part 2 Question if enabled
            if ($request->has('has_part_2')) {
                $this->createPart2($set, $quiz, $validated);
            }
            // Create Part 3 Question
            $this->createPart3($set, $quiz, $validated);
            // Create Part 4 Question
            $this->createPart4($set, $quiz, $validated);

            DB::commit();

            return redirect()->route('admin.writing-sets.index')->with('success', 'Đã tạo bộ đề Writing thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tạo bộ đề: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing the specified Writing Set.
     */
    public function edit(Set $writing_set)
    {
        // Eager load questions sorted by order to easily map them back to the form
        $writing_set->load(['questions' => function ($query) {
            $query->orderBy('order', 'asc');
        }]);
        
        return view('admin.writing-sets.edit', compact('writing_set'));
    }

    /**
     * Update the Cohesive Writing Set and its 4 Parts.
     */
    public function update(Request $request, Set $writing_set)
    {
         $validated = $request->validate([
            'title' => 'required|string|max:255',
            'is_public' => 'boolean',
            'has_part_1' => 'boolean',
            
            // Part 1 Validation
            'part1_instructions' => 'nullable|string',
            'part1_f1_label' => 'nullable|string', 'part1_f1_placeholder' => 'nullable|string',
            'part1_f2_label' => 'nullable|string', 'part1_f2_placeholder' => 'nullable|string',
            'part1_f3_label' => 'nullable|string', 'part1_f3_placeholder' => 'nullable|string',
            'part1_f4_label' => 'nullable|string', 'part1_f4_placeholder' => 'nullable|string',
            'part1_f5_label' => 'nullable|string', 'part1_f5_placeholder' => 'nullable|string',
            'part1_sample_answer' => 'nullable|string',
            
            // Part 2 Validation
            'part2_scenario' => 'nullable|string',
            'part2_hints' => 'nullable|string',
            'part2_min' => 'nullable|integer', 'part2_max' => 'nullable|integer',
            'part2_sample_answer' => 'nullable|string',
            
            // Part 3 Validation
            'part3_stem' => 'nullable|string',
            'part3_prompt_1' => 'required|string', 'part3_prompt_2' => 'required|string', 'part3_prompt_3' => 'required|string',
            'part3_min' => 'nullable|integer', 'part3_max' => 'nullable|integer',
            'part3_sample_1' => 'nullable|string', 'part3_sample_2' => 'nullable|string', 'part3_sample_3' => 'nullable|string',
            
            // Part 4 Validation
            'part4_context' => 'required|string',
            'part4_email_greeting' => 'nullable|string',
            'part4_email_body' => 'required|string',
            'part4_email_sign_off' => 'nullable|string',
            'part4_task1_instruction' => 'required|string',
            'part4_task1_min' => 'nullable|integer', 'part4_task1_max' => 'nullable|integer',
            'part4_task1_sample' => 'nullable|string',
            'part4_task2_instruction' => 'required|string',
            'part4_task2_min' => 'nullable|integer', 'part4_task2_max' => 'nullable|integer',
            'part4_task2_sample' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $writing_set->update([
                'title' => $validated['title'],
                'is_public' => $request->has('is_public')
            ]);

            $questions = $writing_set->questions()->orderBy('order', 'asc')->get();

            $p1 = $questions->where('part', 1)->first();
            $hasPart1 = $request->has('has_part_1');

            if ($hasPart1) {
                if ($p1) {
                    // Update existing Part 1
                    $p1Metadata = $p1->metadata ?? [];
                    $p1Metadata['instructions'] = $validated['part1_instructions'] ?? '';
                    $p1Metadata['fields'] = [
                        ['label' => $validated['part1_f1_label'] ?? '', 'placeholder' => $validated['part1_f1_placeholder'] ?? ''],
                        ['label' => $validated['part1_f2_label'] ?? '', 'placeholder' => $validated['part1_f2_placeholder'] ?? ''],
                        ['label' => $validated['part1_f3_label'] ?? '', 'placeholder' => $validated['part1_f3_placeholder'] ?? ''],
                        ['label' => $validated['part1_f4_label'] ?? '', 'placeholder' => $validated['part1_f4_placeholder'] ?? ''],
                        ['label' => $validated['part1_f5_label'] ?? '', 'placeholder' => $validated['part1_f5_placeholder'] ?? ''],
                    ];
                    $p1Metadata['sample_answer'] = $validated['part1_sample_answer'] ?? '';
                    $p1->update(['metadata' => $p1Metadata]);
                } else {
                    // Create new Part 1
                    $quiz = $writing_set->quiz;
                    $this->createPart1($writing_set, $quiz, $validated);
                }
            } else {
                if ($p1) {
                    $p1->delete();
                }
            }

            // Update Part 2
            $p2 = $questions->where('part', 2)->first();
            $hasPart2 = $request->has('has_part_2');

            if ($hasPart2) {
                if ($p2) {
                    $p2Metadata = $p2->metadata ?? [];
                    $p2Metadata['scenario'] = $validated['part2_scenario'] ?? '';
                    $p2Metadata['hints'] = $validated['part2_hints'] ?? '';
                    $p2Metadata['word_limit'] = ['min' => (int)($validated['part2_min'] ?? 20), 'max' => (int)($validated['part2_max'] ?? 30)];
                    $p2Metadata['sample_answer'] = $validated['part2_sample_answer'] ?? '';
                    $p2->update(['metadata' => $p2Metadata]);
                } else {
                    $quiz = $writing_set->quiz;
                    $this->createPart2($writing_set, $quiz, $validated);
                }
            } else {
                if ($p2) {
                    $p2->delete();
                }
            }

            // Update Part 3
            $p3 = $questions->where('part', 3)->first();
            if ($p3) {
                $p3Metadata = $p3->metadata ?? [];
                $p3Stem = $validated['part3_stem'] ?? "Respond to the messages in the group.";
                $p3_min = (int)($validated['part3_min'] ?? 30);
                $p3_max = (int)($validated['part3_max'] ?? 40);
                $p3Metadata['questions'][0] = ['prompt' => $validated['part3_prompt_1'], 'word_limit' => ['min' => $p3_min, 'max' => $p3_max], 'sample_answer' => $validated['part3_sample_1'] ?? ''];
                $p3Metadata['questions'][1] = ['prompt' => $validated['part3_prompt_2'], 'word_limit' => ['min' => $p3_min, 'max' => $p3_max], 'sample_answer' => $validated['part3_sample_2'] ?? ''];
                $p3Metadata['questions'][2] = ['prompt' => $validated['part3_prompt_3'], 'word_limit' => ['min' => $p3_min, 'max' => $p3_max], 'sample_answer' => $validated['part3_sample_3'] ?? ''];
                $p3->update(['stem' => $p3Stem, 'metadata' => $p3Metadata]);
            }

            // Update Part 4
            $p4 = $questions->where('part', 4)->first();
            if ($p4) {
                $p4Metadata = $p4->metadata ?? [];
                $p4Metadata['context'] = $validated['part4_context'];
                $p4Metadata['email']['greeting'] = $validated['part4_email_greeting'] ?? 'Dear Member,';
                $p4Metadata['email']['body'] = $validated['part4_email_body'];
                $p4Metadata['email']['sign_off'] = $validated['part4_email_sign_off'] ?? "Best regards,\nThe Management";
                
                $p4Metadata['task1']['instruction'] = $validated['part4_task1_instruction'];
                $p4Metadata['task1']['word_limit'] = ['min' => (int)($validated['part4_task1_min'] ?? 40), 'max' => (int)($validated['part4_task1_max'] ?? 50)];
                $p4Metadata['task1']['sample_answer'] = $validated['part4_task1_sample'] ?? '';
                
                $p4Metadata['task2']['instruction'] = $validated['part4_task2_instruction'];
                $p4Metadata['task2']['word_limit'] = ['min' => (int)($validated['part4_task2_min'] ?? 120), 'max' => (int)($validated['part4_task2_max'] ?? 150)];
                $p4Metadata['task2']['sample_answer'] = $validated['part4_task2_sample'] ?? '';
                
                $p4->update(['metadata' => $p4Metadata]);
            }

            DB::commit();

            return redirect()->route('admin.writing-sets.index')->with('success', 'Đã cập nhật bộ đề Writing thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật bộ đề: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified Writing Set from storage.
     */
    public function destroy(Set $writing_set)
    {
        try {
            DB::beginTransaction();
            // Delete questions associated with this set
            foreach ($writing_set->questions as $question) {
                $question->delete();
            }
            // Delete the set
            $writing_set->delete();
            DB::commit();
            return redirect()->route('admin.writing-sets.index')->with('success', 'Đã xoá bộ đề thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi xoá bộ đề: ' . $e->getMessage());
        }
    }

    /* Private Helpers to Create Questions */

    private function createPart1(Set $set, Quiz $quiz, array $validated)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'writing',
            'part' => 1,
            'type' => 'writing-part-1',
            'title' => "{$validated['title']} - Part 1",
            'stem' => "Please fill in the form below.",
            'point' => 5,
            'order' => 1,
            'metadata' => [
                'instructions' => $validated['part1_instructions'] ?? '',
                'fields' => [
                    ['label' => $validated['part1_f1_label'] ?? '', 'placeholder' => $validated['part1_f1_placeholder'] ?? ''],
                    ['label' => $validated['part1_f2_label'] ?? '', 'placeholder' => $validated['part1_f2_placeholder'] ?? ''],
                    ['label' => $validated['part1_f3_label'] ?? '', 'placeholder' => $validated['part1_f3_placeholder'] ?? ''],
                    ['label' => $validated['part1_f4_label'] ?? '', 'placeholder' => $validated['part1_f4_placeholder'] ?? ''],
                    ['label' => $validated['part1_f5_label'] ?? '', 'placeholder' => $validated['part1_f5_placeholder'] ?? ''],
                ],
                'sample_answer' => $validated['part1_sample_answer'] ?? '',
            ]
        ]);
        $set->questions()->attach($question->id);
    }

    private function createPart2(Set $set, Quiz $quiz, array $validated)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'writing',
            'part' => 2,
            'type' => 'writing-part-2',
            'title' => "{$validated['title']} - Part 2",
            'stem' => "Write a short text (20-30 words).",
            'point' => 5,
            'order' => 2,
            'metadata' => [
                'scenario' => $validated['part2_scenario'] ?? '',
                'word_limit' => ['min' => (int)($validated['part2_min'] ?? 20), 'max' => (int)($validated['part2_max'] ?? 30)],
                'hints' => $validated['part2_hints'] ?? '',
                'sample_answer' => $validated['part2_sample_answer'] ?? '',
            ]
        ]);
        $set->questions()->attach($question->id);
    }
    
    private function createPart3(Set $set, Quiz $quiz, array $validated)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'writing',
            'part' => 3,
            'type' => 'writing-part-3',
            'title' => "{$validated['title']} - Part 3",
            'stem' => $validated['part3_stem'] ?? "Respond to the messages in the group.",
            'point' => 10,
            'order' => 3, 
            'metadata' => [
                'questions' => [
                    ['prompt' => $validated['part3_prompt_1'], 'word_limit' => ['min' => (int)($validated['part3_min'] ?? 30), 'max' => (int)($validated['part3_max'] ?? 40)], 'sample_answer' => $validated['part3_sample_1'] ?? ''],
                    ['prompt' => $validated['part3_prompt_2'], 'word_limit' => ['min' => (int)($validated['part3_min'] ?? 30), 'max' => (int)($validated['part3_max'] ?? 40)], 'sample_answer' => $validated['part3_sample_2'] ?? ''],
                    ['prompt' => $validated['part3_prompt_3'], 'word_limit' => ['min' => (int)($validated['part3_min'] ?? 30), 'max' => (int)($validated['part3_max'] ?? 40)], 'sample_answer' => $validated['part3_sample_3'] ?? ''],
                ]
            ]
        ]);
        $set->questions()->attach($question->id);
    }

    private function createPart4(Set $set, Quiz $quiz, array $validated)
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'skill' => 'writing',
            'part' => 4,
            'type' => 'writing-part-4',
            'title' => "{$validated['title']} - Part 4",
            'stem' => "Read the email and complete the two tasks.",
            'point' => 20, 
            'order' => 4,
            'metadata' => [
                'context' => $validated['part4_context'],
                'email' => [
                    'greeting' => $validated['part4_email_greeting'] ?? "Dear Member,",
                    'body' => $validated['part4_email_body'],
                    'sign_off' => $validated['part4_email_sign_off'] ?? "Best regards,\nThe Management"
                ],
                'task1' => [
                    'instruction' => $validated['part4_task1_instruction'],
                    'word_limit' => ['min' => (int)($validated['part4_task1_min'] ?? 40), 'max' => (int)($validated['part4_task1_max'] ?? 50)],
                    'sample_answer' => $validated['part4_task1_sample'] ?? '',
                ],
                'task2' => [
                    'instruction' => $validated['part4_task2_instruction'],
                    'word_limit' => ['min' => (int)($validated['part4_task2_min'] ?? 120), 'max' => (int)($validated['part4_task2_max'] ?? 150)],
                    'sample_answer' => $validated['part4_task2_sample'] ?? '',
                ]
            ]
        ]);
        $set->questions()->attach($question->id);
    }
}
