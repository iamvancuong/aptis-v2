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
                  ->where('quizzes.skill', '!=', 'writing') // Managed by WritingSetController
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
        $newOrder = (int) $request->input('order', 0);

        // Shift existing sets at this order or above to make room
        Set::where('quiz_id', $validated['quiz_id'])
            ->where('order', '>=', $newOrder)
            ->increment('order');

        $validated['order'] = $newOrder;
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
            $newOrder = (int) $request->input('order');
            $oldOrder = $set->order;
            $quizId = $validated['quiz_id'];

            if ($newOrder !== $oldOrder) {
                // Temporarily move current set out of the way
                $set->update(['order' => -1]);

                if ($newOrder > $oldOrder) {
                    // Moving down: shift items between oldOrder+1..newOrder up by -1
                    Set::where('quiz_id', $quizId)
                        ->whereBetween('order', [$oldOrder + 1, $newOrder])
                        ->decrement('order');
                } else {
                    // Moving up: shift items between newOrder..oldOrder-1 down by +1
                    Set::where('quiz_id', $quizId)
                        ->whereBetween('order', [$newOrder, $oldOrder - 1])
                        ->increment('order');
                }
            }

            $validated['order'] = $newOrder;
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
