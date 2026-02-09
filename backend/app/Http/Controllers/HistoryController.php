<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index()
    {
        $attempts = auth()->user()
            ->attempts()
            ->with(['set'])
            ->latest()
            ->paginate(20);

        return view('history.index', compact('attempts'));
    }

    public function show(Attempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $attempt->load(['attemptAnswers.question']);

        return view('history.show', compact('attempt'));
    }
}
