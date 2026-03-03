<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Set;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpeakingSetController extends Controller
{
    /**
     * Display a listing of Speaking Sets.
     */
    public function index()
    {
        // Fetch Sets that belong to Speaking Skill 'Part 1'
        $sets = Set::whereHas('quiz', function ($query) {
            $query->where('skill', 'speaking')
                  ->where('part', 1);
        })->withCount('questions')->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.speaking-sets.index', compact('sets'));
    }

    /**
     * Show the form for creating a new Speaking Set.
     */
    public function create()
    {
        return view('admin.speaking-sets.create');
    }

    /**
     * Store a newly created Speaking Set along with all 4 Parts.
     */
    public function store(Request $request)
    {
        $validated = $this->validateSpeakingRequest($request);

        try {
            DB::beginTransaction();

            // Ensure the Speaking Quiz exists (Part 1 as the anchor)
            $quiz = Quiz::firstOrCreate(
                ['skill' => 'speaking', 'part' => 1],
                [
                    'title' => 'Speaking',
                    'duration_minutes' => 12, // approx duration
                    'metadata' => []
                ]
            );

            // Create Set
            $set = Set::create([
                'quiz_id' => $quiz->id,
                'title' => $validated['title'],
                'is_public' => $request->has('is_public'),
                'order' => Set::where('quiz_id', $quiz->id)->max('order') + 1,
            ]);

            // Create 4 Parts
            $this->createOrUpdatePart1($set, $quiz, $validated);
            $this->createOrUpdatePart2($set, $quiz, $validated, $request);
            $this->createOrUpdatePart3($set, $quiz, $validated, $request);
            $this->createOrUpdatePart4($set, $quiz, $validated, $request);

            DB::commit();

            return redirect()->route('admin.speaking-sets.index')->with('success', 'Đã tạo bộ đề Speaking thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tạo bộ đề: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified Speaking Set.
     */
    public function show(Set $speaking_set)
    {
        $speaking_set->load(['questions' => function ($query) {
            $query->orderBy('order', 'asc');
        }]);
        
        return view('admin.speaking-sets.show', compact('speaking_set'));
    }

    /**
     * Show the form for editing the specified Speaking Set.
     */
    public function edit(Set $speaking_set)
    {
        // Load questions sorted by order
        $speaking_set->load(['questions' => function ($query) {
            $query->orderBy('order', 'asc');
        }]);
        
        return view('admin.speaking-sets.edit', compact('speaking_set'));
    }

    /**
     * Update the Speaking Set and its 4 Parts.
     */
    public function update(Request $request, Set $speaking_set)
    {
        $validated = $this->validateSpeakingRequest($request, true);

        try {
            DB::beginTransaction();

            $speaking_set->update([
                'title' => $validated['title'],
                'is_public' => $request->has('is_public')
            ]);
            
            $quiz = $speaking_set->quiz;

            $this->createOrUpdatePart1($speaking_set, $quiz, $validated);
            $this->createOrUpdatePart2($speaking_set, $quiz, $validated, $request);
            $this->createOrUpdatePart3($speaking_set, $quiz, $validated, $request);
            $this->createOrUpdatePart4($speaking_set, $quiz, $validated, $request);

            DB::commit();

            return redirect()->route('admin.speaking-sets.index')->with('success', 'Đã cập nhật bộ đề Speaking thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật bộ đề: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified Speaking Set from storage.
     */
    public function destroy(Set $speaking_set)
    {
        try {
            DB::beginTransaction();
            // Delete associated images
            foreach ($speaking_set->questions as $question) {
                if (!empty($question->metadata['image_path'])) {
                    Storage::disk('public')->delete($question->metadata['image_path']);
                }
                if (!empty($question->metadata['image_paths'])) {
                    foreach ($question->metadata['image_paths'] as $path) {
                        Storage::disk('public')->delete($path);
                    }
                }
                $question->delete();
            }
            // Delete the set
            $speaking_set->delete();
            DB::commit();
            return redirect()->route('admin.speaking-sets.index')->with('success', 'Đã xoá bộ đề thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi xoá bộ đề: ' . $e->getMessage());
        }
    }

    /* --- PRIVATE METHODS --- */

    private function validateSpeakingRequest(Request $request, $isUpdate = false)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'is_public' => 'boolean',
            
            // Part 1 - Optional
            'part1_q1' => 'nullable|string',
            'part1_q2' => 'nullable|string',
            'part1_q3' => 'nullable|string',
            'part1_sample_answer' => 'nullable|string',
            
            // Part 2
            'part2_q1' => 'required|string',
            'part2_q2' => 'required|string',
            'part2_q3' => 'required|string',
            'part2_sample_answer' => 'nullable|string',
            
            // Part 3
            'part3_q1' => 'required|string',
            'part3_q2' => 'required|string',
            'part3_q3' => 'required|string',
            'part3_sample_answer' => 'nullable|string',
            
            // Part 4
            'part4_q1' => 'required|string',
            'part4_q2' => 'required|string',
            'part4_q3' => 'required|string',
            'part4_sample_answer' => 'nullable|string',

            // Delete Flags
            'delete_part2_image' => 'nullable|boolean',
            'delete_part3_image1' => 'nullable|boolean',
            'delete_part3_image2' => 'nullable|boolean',
            'delete_part4_image' => 'nullable|boolean',
        ];

        if (!$isUpdate) {
            $rules['part2_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part3_image1'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part3_image2'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part4_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
        } else {
            $rules['part2_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part3_image1'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part3_image2'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
            $rules['part4_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048';
        }

        return $request->validate($rules);
    }

    private function createOrUpdatePart1(Set $set, Quiz $quiz, array $validated)
    {
        // If Part 1 questions are all empty AND sample answer is empty, detach/remove from set
        if (empty($validated['part1_q1']) && empty($validated['part1_q2']) && empty($validated['part1_q3']) && empty($validated['part1_sample_answer'])) {
            $question = $set->questions()->where('part', 1)->first();
            if ($question) {
                $set->questions()->detach($question->id);
            }
            return;
        }

        $metadata = [
            'questions' => [
                $validated['part1_q1'] ?? '',
                $validated['part1_q2'] ?? '',
                $validated['part1_q3'] ?? ''
            ],
            'sample_answer' => $validated['part1_sample_answer'] ?? '',
            'prep_time' => 0,
            'answer_time_per_question' => 30
        ];

        $question = $set->questions()->where('part', 1)->first();
        if ($question) {
            $question->update(['metadata' => $metadata]);
        } else {
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'skill' => 'speaking',
                'part' => 1,
                'type' => 'speaking-part-1',
                'stem' => "In this part, I am going to ask you three short questions about yourself and your interests. You will have 30 seconds to reply to each question.",
                'point' => 5,
                'order' => 1,
                'metadata' => $metadata
            ]);
            $set->questions()->attach($q->id);
        }
    }

    private function createOrUpdatePart2(Set $set, Quiz $quiz, array $validated, Request $request)
    {
        $question = $set->questions()->where('part', 2)->first();
        $imagePath = $question->metadata['image_path'] ?? null;

        if ($request->hasFile('part2_image')) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            $imagePath = $request->file('part2_image')->store('speaking_images', 'public');
        } elseif ($request->boolean('delete_part2_image')) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $metadata = [
            'image_path' => $imagePath,
            'questions' => [
                $validated['part2_q1'],
                $validated['part2_q2'],
                $validated['part2_q3']
            ],
            'sample_answer' => $validated['part2_sample_answer'] ?? '',
            'prep_time' => 0,
            'answer_time_per_question' => 45
        ];

        if ($question) {
            $question->update(['metadata' => $metadata]);
        } else {
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'skill' => 'speaking',
                'part' => 2,
                'type' => 'speaking-part-2',
                'title' => "{$validated['title']} - Part 2",
                'stem' => "In this part, I'm going to ask you to describe a picture. Then I will ask you two questions about it. You will have 45 seconds for each response.",
                'point' => 5,
                'order' => 2,
                'metadata' => $metadata
            ]);
            $set->questions()->attach($q->id);
        }
    }

    private function createOrUpdatePart3(Set $set, Quiz $quiz, array $validated, Request $request)
    {
        $question = $set->questions()->where('part', 3)->first();
        $imagePaths = $question->metadata['image_paths'] ?? [null, null];

        if ($request->hasFile('part3_image1')) {
            if ($imagePaths[0]) Storage::disk('public')->delete($imagePaths[0]);
            $imagePaths[0] = $request->file('part3_image1')->store('speaking_images', 'public');
        } elseif ($request->boolean('delete_part3_image1')) {
            if ($imagePaths[0]) Storage::disk('public')->delete($imagePaths[0]);
            $imagePaths[0] = null;
        }
        
        if ($request->hasFile('part3_image2')) {
            if ($imagePaths[1]) Storage::disk('public')->delete($imagePaths[1]);
            $imagePaths[1] = $request->file('part3_image2')->store('speaking_images', 'public');
        } elseif ($request->boolean('delete_part3_image2')) {
            if ($imagePaths[1]) Storage::disk('public')->delete($imagePaths[1]);
            $imagePaths[1] = null;
        }

        $metadata = [
            'image_paths' => $imagePaths,
            'questions' => [
                $validated['part3_q1'],
                $validated['part3_q2'],
                $validated['part3_q3']
            ],
            'sample_answer' => $validated['part3_sample_answer'] ?? '',
            'prep_time' => 0,
            'answer_time_per_question' => 45
        ];

        if ($question) {
            $question->update(['metadata' => $metadata]);
        } else {
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'skill' => 'speaking',
                'part' => 3,
                'type' => 'speaking-part-3',
                'title' => "{$validated['title']} - Part 3",
                'stem' => "In this part, I'm going to ask you to compare two pictures, and I will then ask you two questions about them. You will have 45 seconds for each response.",
                'point' => 10,
                'order' => 3,
                'metadata' => $metadata
            ]);
            $set->questions()->attach($q->id);
        }
    }

    private function createOrUpdatePart4(Set $set, Quiz $quiz, array $validated, Request $request)
    {
        $question = $set->questions()->where('part', 4)->first();
        $imagePath = $question->metadata['image_path'] ?? null;

        if ($request->hasFile('part4_image')) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            $imagePath = $request->file('part4_image')->store('speaking_images', 'public');
        } elseif ($request->boolean('delete_part4_image')) {
            if ($imagePath) Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        $metadata = [
            'image_path' => $imagePath,
            'questions' => [
                $validated['part4_q1'],
                $validated['part4_q2'],
                $validated['part4_q3']
            ],
            'sample_answer' => $validated['part4_sample_answer'] ?? '',
            'prep_time' => 60,
            'total_answer_time' => 120
        ];

        if ($question) {
            $question->update(['metadata' => $metadata]);
        } else {
            $q = Question::create([
                'quiz_id' => $quiz->id,
                'skill' => 'speaking',
                'part' => 4,
                'type' => 'speaking-part-4',
                'title' => "{$validated['title']} - Part 4",
                'stem' => "In this part, I'm going to show you a picture and ask you three questions. You will have one minute to think about your answers before you start speaking. You will have two minutes to answer all three questions.",
                'point' => 20,
                'order' => 4,
                'metadata' => $metadata
            ]);
            $set->questions()->attach($q->id);
        }
    }
}
