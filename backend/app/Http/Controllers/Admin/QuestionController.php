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
     * Display a listing of questions grouped by Quiz/Part.
     */
    public function index(Request $request)
    {
        $questions = $this->questionService->getQuestions($request->all());
        $quizzes = Quiz::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'quizzes'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create()
    {
        $quizzes = Quiz::orderBy('name')->get();

        return view('admin.questions.create', compact('quizzes'));
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(StoreQuestionRequest $request)
    {
        $this->questionService->createQuestion(
            $request->validated(),
            $request->file('image'),
            $request->input('set_id')
        );

        return redirect()->route('admin.questions.index')
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
        $quizzes = Quiz::orderBy('name')->get();
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
            $request->file('image')
        );

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Question $question)
    {
        $this->questionService->deleteQuestion($question);

        if (request()->wantsJson()) {
            return response()->json([
                'message' => 'Question deleted successfully.',
                'id' => $question->id
            ]);
        }

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    /**
     * Get sets for a specific quiz (API).
     */
    public function getSetsByQuiz($quizId)
    {
        $sets = $this->questionService->getSetsByQuiz($quizId);
        $quiz = $this->questionService->getQuizDetails($quizId);

        return response()->json([
            'sets' => $sets,
            'quiz' => [
                'skill' => $quiz->skill,
                'part' => $quiz->part,
            ]
        ]);
    }
}
