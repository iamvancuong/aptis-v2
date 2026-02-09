<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        
        $quizzes = Quiz::orderBy('skill')
                      ->orderBy('part')
                      ->paginate($perPage)
                      ->withQueryString();
                      
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        return view('admin.quizzes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'skill' => 'required|in:reading,listening,writing',
            'part' => 'required|integer|between:1,4',
            'duration_minutes' => 'nullable|integer',
            'is_published' => 'boolean',
            'metadata' => 'nullable|json',
        ]);

        $validated['is_published'] = $request->has('is_published');

        Quiz::create($validated);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz created successfully.');
    }

    public function edit(Quiz $quiz)
    {
        return view('admin.quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'skill' => 'required|in:reading,listening,writing',
            'part' => 'required|integer|between:1,4',
            'duration_minutes' => 'nullable|integer',
            'is_published' => 'boolean',
            'metadata' => 'nullable|json',
        ]);

        $validated['is_published'] = $request->has('is_published');

        $quiz->update($validated);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }
}
