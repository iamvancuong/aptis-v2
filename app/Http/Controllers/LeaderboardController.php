<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $skill = $request->get('skill', 'reading');

        // Top 20 completed mock tests for this skill, highest score first
        $leaderboard = MockTest::with('user')
            ->where('skill', $skill)
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->orderByDesc('score')
            ->orderBy('duration_seconds') // tiebreak: faster is better
            ->limit(50)
            ->get()
            ->unique('user_id') // one entry per user (best score)
            ->take(20)
            ->values();

        // Current user's best score for this skill
        $myBest = MockTest::where('user_id', auth()->id())
            ->where('skill', $skill)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->first();

        return view('leaderboard.index', compact('leaderboard', 'skill', 'myBest'));
    }
}
