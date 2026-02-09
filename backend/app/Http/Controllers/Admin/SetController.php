<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Set;
use App\Models\Quiz;
use Illuminate\Http\Request;

class SetController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        
        // Get paginated sets with quiz relationship
        $sets = Set::with('quiz')
                  ->join('quizzes', 'sets.quiz_id', '=', 'quizzes.id')
                  ->select('sets.*')
                  ->orderBy('quizzes.skill')
                  ->orderBy('quizzes.part')
                  ->orderBy('sets.order')
                  ->paginate($perPage)
                  ->withQueryString();
        
        return view('admin.sets.index', compact('sets'));
    }

    public function create()
    {
        $quizzes = Quiz::orderBy('skill')->orderBy('part')->get();
        return view('admin.sets.create', compact('quizzes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_public' => 'boolean',
            'metadata' => 'nullable|json',
        ]);

        $validated['is_public'] = $request->has('is_public');
        $validated['order'] = $request->input('order', 0);

        Set::create($validated);

        return redirect()->route('admin.sets.index')
            ->with('success', 'Set created successfully.');
    }

    public function edit(Set $set)
    {
        $quizzes = Quiz::orderBy('skill')->orderBy('part')->get();
        return view('admin.sets.edit', compact('set', 'quizzes'));
    }

    public function update(Request $request, Set $set)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer',
            'is_public' => 'boolean',
            'metadata' => 'nullable|json',
        ]);

        $validated['is_public'] = $request->has('is_public');
        if ($request->has('order')) {
            $validated['order'] = $request->input('order');
        }

        $set->update($validated);

        return redirect()->route('admin.sets.index')
            ->with('success', 'Set updated successfully.');
    }

    public function destroy(Set $set)
    {
        $set->delete();

        return redirect()->route('admin.sets.index')
            ->with('success', 'Set deleted successfully.');
    }
}
