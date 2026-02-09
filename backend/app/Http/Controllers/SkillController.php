<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function show($skill)
    {
        // Validate skill
        if (!in_array($skill, ['reading', 'listening', 'writing'])) {
            abort(404);
        }

        // Get all quizzes (parts) for this skill
        $quizzes = Quiz::where('skill', $skill)
            ->where('is_published', true)
            ->orderBy('part')
            ->get();

        return view('skills.show', compact('skill', 'quizzes'));
    }
}
