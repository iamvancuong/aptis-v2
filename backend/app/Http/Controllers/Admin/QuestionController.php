<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    protected QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Display a listing of Reading questions grouped by Quiz/Part.
     */
    public function readingIndex(Request $request)
    {
        $request->merge(['skill' => 'reading']);
        $questions = $this->questionService->getQuestions($request->all());
        
        if ($questions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
             $questions->getCollection()->each(function ($question) {
                 $question->load('sets');
             });
        }

        $quizzes = Quiz::where('skill', 'reading')->orderBy('title')->get();
        $currentSkill = 'reading';

        return view('admin.questions.index', compact('questions', 'quizzes', 'currentSkill'));
    }

    /**
     * Display a listing of Listening questions grouped by Quiz/Part.
     */
    public function listeningIndex(Request $request)
    {
        $request->merge(['skill' => 'listening']);
        $questions = $this->questionService->getQuestions($request->all());
        
        if ($questions instanceof \Illuminate\Pagination\LengthAwarePaginator) {
             $questions->getCollection()->each(function ($question) {
                 $question->load('sets');
             });
        }

        $quizzes = Quiz::where('skill', 'listening')->orderBy('title')->get();
        $currentSkill = 'listening';

        return view('admin.questions.index', compact('questions', 'quizzes', 'currentSkill'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create()
    {
        $skill = request('skill');
        $quizzesQuery = Quiz::where('skill', '!=', 'writing')->orderBy('title');
        if ($skill) {
            $quizzesQuery->where('skill', $skill);
        }
        $quizzes = $quizzesQuery->get();

        return view('admin.questions.create', compact('quizzes', 'skill'));
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(StoreQuestionRequest $request)
    {
        $this->questionService->createQuestion(
            $request->validated(),
            $request->file('audio'),
            $request->file('speaker_audio'),
            $request->input('set_id')
        );

        $skill = $request->input('skill', 'reading');
        return redirect()->route("admin.questions.{$skill}")
            ->with('success', 'Question created successfully.');
    }

    /**
     * Display the specified question.
     */
    public function show(Question $question)
    {
        $question->load(['quiz', 'sets']);

        return view('admin.questions.show', compact('question'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Question $question)
    {
        $question->load('sets'); // Eager load sets for JSON serialization in view
        $quizzes = Quiz::where('skill', '!=', 'writing')->orderBy('title')->get();
        // Load sets for the selected quiz to populate the dropdown
        $sets = $this->questionService->getSetsByQuiz($question->quiz_id);

        return view('admin.questions.edit', compact('question', 'quizzes', 'sets'));
    }

    /**
     * Update the specified question in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->questionService->updateQuestion(
            $question,
            $request->validated(),
            $request->file('audio'),
            $request->file('speaker_audio')
        );

        $skill = $request->input('skill', 'reading');
        return redirect()->route("admin.questions.{$skill}")
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Question $question)
    {
        $skill = $question->quiz->skill ?? 'reading';
        $this->questionService->deleteQuestion($question);

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Question deleted successfully.',
                'id' => $question->id
            ]);
        }

        return redirect()->route("admin.questions.{$skill}")
            ->with('success', 'Question deleted successfully.');
    }

    /**
     * Get sets for a specific quiz (API).
     */
    public function getSetsByQuiz($quizId)
    {
        $sets = $this->questionService->getSetsByQuiz($quizId);
        $quiz = $this->questionService->getQuizDetails($quizId);

        $maxOrder = \App\Models\Question::where('quiz_id', $quizId)->max('order');

        return response()->json([
            'sets'      => $sets,
            'max_order' => $maxOrder !== null ? (int)$maxOrder + 1 : 0,
            'quiz'      => [
                'skill' => $quiz->skill,
                'part'  => $quiz->part,
            ]
        ]);
    }

    /**
     * Import questions from JSON.
     */
    public function import(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'set_id' => 'required|exists:sets,id',
            'file' => 'required|file', // Accepts .json from view
        ]);

        $quizId = $request->quiz_id;
        $setId = $request->set_id;
        $skill = $request->get('skill', 'reading');

        $json = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($json, true);

        if (!$data) {
            return back()->with('error', 'Invalid JSON format.');
        }

        // Support both single question object or array of questions (from export)
        $questionsData = isset($data['questions']) ? $data['questions'] : [$data];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($questionsData as $qData) {
                // Map metadata if it's from old export format (Listening)
                $mappedMetadata = $qData['metadata'] ?? [];
                $part = $qData['part'] ?? 1;

                if ($skill === 'listening') {
                    // Logic from ImportListeningJson command
                    if ($part == 1) {
                        if (isset($mappedMetadata['options'])) $mappedMetadata['choices'] = $mappedMetadata['options'];
                        if (isset($mappedMetadata['correct_index'])) $mappedMetadata['correct_answer'] = (int) $mappedMetadata['correct_index'];
                    } elseif ($part == 2) {
                        if (isset($mappedMetadata['options'])) $mappedMetadata['choices'] = $mappedMetadata['options'];
                        if (isset($mappedMetadata['answers'])) $mappedMetadata['correct_answers'] = array_map('intval', $mappedMetadata['answers']);
                        if (isset($mappedMetadata['speakers'])) {
                            $items = []; $audioFiles = [];
                            foreach ($mappedMetadata['speakers'] as $speaker) {
                                $items[] = $speaker['label'] ?? '';
                                $audioFiles[] = $speaker['audio'] ?? null;
                            }
                            $mappedMetadata['items'] = $items;
                            $mappedMetadata['audio_files'] = $audioFiles;
                        }
                    } elseif ($part == 3) {
                        if (isset($mappedMetadata['options'])) $mappedMetadata['shared_choices'] = $mappedMetadata['options'];
                        if (isset($mappedMetadata['items'])) $mappedMetadata['statements'] = $mappedMetadata['items'];
                        if (isset($mappedMetadata['answers'])) $mappedMetadata['correct_answers'] = array_map('intval', $mappedMetadata['answers']);
                    } elseif ($part == 4) {
                        if (isset($mappedMetadata['questions'])) {
                            $newQs = []; $corrects = [];
                            foreach ($mappedMetadata['questions'] as $q) {
                                $newQs[] = ['question' => $q['stem'] ?? '', 'choices' => $q['options'] ?? []];
                                $corrects[] = isset($q['correct_index']) ? (int) $q['correct_index'] : 0;
                            }
                            $mappedMetadata['questions'] = $newQs;
                            $mappedMetadata['correct_answers'] = $corrects;
                        }
                    }
                }

                $explanation = $qData['explanation'] ?? ($mappedMetadata['description'] ?? null);

                $question = Question::create([
                    'quiz_id' => $quizId,
                    'title' => $qData['title'] ?? ($qData['stem'] ?? 'Imported Question'),
                    'stem' => $qData['stem'] ?? null,
                    'explanation' => $explanation,
                    'skill' => $skill,
                    'part' => $part,
                    'type' => $qData['type'] ?? 'multiple_choice',
                    'order' => $qData['order'] ?? 0,
                    'point' => $qData['point'] ?? 1,
                    'audio_path' => $qData['audio_path'] ?? null,
                    'image_path' => $qData['image_path'] ?? null,
                    'metadata' => $mappedMetadata,
                ]);

                $question->sets()->attach($setId);
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', 'Import questions successfully!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error importing: ' . $e->getMessage());
        }
    }
}
