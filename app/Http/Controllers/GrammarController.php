<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Set;

class GrammarController extends Controller
{
    public function index()
    {
        $quiz = Quiz::where('skill', 'grammar')->first();

        $sets = $quiz
            ? Set::where('quiz_id', $quiz->id)
                ->where('status', 'published')
                ->withCount('questions')
                ->latest()
                ->get()
            : collect();

        return view('grammar.index', compact('sets', 'quiz'));
    }
}
