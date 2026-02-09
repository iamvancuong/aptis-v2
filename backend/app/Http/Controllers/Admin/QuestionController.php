<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions grouped by Quiz/Part.
     */
    public function index(Request $request)
    {
        $query = Question::with('quiz');

        // Filter by quiz
        if ($request->filled('quiz_id')) {
            $query->where('quiz_id', $request->quiz_id);
        }

        // Filter by skill
        if ($request->filled('skill')) {
            $query->where('skill', $request->skill);
        }

        // Filter by part
        if ($request->filled('part')) {
            $query->where('part', $request->part);
        }

        // Order by quiz, part, order
        $questions = $query->orderBy('quiz_id')
            ->orderBy('part')
            ->orderBy('order')
            ->paginate($request->get('per_page', 20))
            ->withQueryString();

        // Get quizzes for filter dropdown
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
        $data = $request->validated();

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('questions', 'public');
        }

        // Create question
        $question = Question::create($data);

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    /**
     * Display the specified question.
     */
    public function show(Question $question)
    {
        $question->load('quiz');

        return view('admin.questions.show', compact('question'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Question $question)
    {
        $quizzes = Quiz::orderBy('name')->get();

        return view('admin.questions.edit', compact('question', 'quizzes'));
    }

    /**
     * Update the specified question in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $data = $request->validated();

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }
            $data['image_path'] = $request->file('image')->store('questions', 'public');
        }

        // Update question
        $question->update($data);

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Question $question)
    {
        // Delete associated image
        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return redirect()->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }
}
