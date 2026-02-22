<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function index()
    {
        $attempts = auth()->user()->attempts()
            ->with(['set.quiz'])
            ->latest()
            ->paginate(10);

        return view('attempts.index', compact('attempts'));
    }

    public function show(Attempt $attempt)
    {
        $this->authorize('view', $attempt); // Ensure user owns attempt

        $attempt->load(['attemptAnswers.question', 'set.quiz']);

        return view('attempts.show', compact('attempt'));
    }
}
