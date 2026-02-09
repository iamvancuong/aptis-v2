<?php

namespace App\Http\Controllers;

use App\Models\Set;
use App\Models\Quiz;
use Illuminate\Http\Request;

class SetController extends Controller
{
    public function index($skill, $part)
    {
        // Validate skill
        if (!in_array($skill, ['reading', 'listening', 'writing'])) {
            abort(404);
        }

        // Get the quiz for this skill + part
        $quiz = Quiz::where('skill', $skill)
            ->where('part', $part)
            ->where('is_published', true)
            ->firstOrFail();

        // Get all sets for this quiz
        $sets = $quiz->sets()
            ->where('is_public', true)
            ->orderBy('order')
            ->get();

        return view('sets.index', compact('skill', 'part', 'quiz', 'sets'));
    }
}
