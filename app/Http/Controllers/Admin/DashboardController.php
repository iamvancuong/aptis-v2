<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\MockTest;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Overview counts
        $totalUsers = User::count();
        $totalMockTests = MockTest::count();
        
        // 2. Mock Test status distribution
        $mockTestStats = [
            'completed' => MockTest::where('status', 'completed')->count(),
            'in_progress' => MockTest::where('status', 'in_progress')->count(),
            'total' => $totalMockTests,
        ];

        // 3. Writing Reviews needing attention
        $pendingWritings = AttemptAnswer::whereHas('question', function ($q) {
                $q->where('skill', 'writing');
            })
            ->where('grading_status', 'pending')
            ->count();

        $aiGradedWritings = AttemptAnswer::whereHas('question', function ($q) {
                $q->where('skill', 'writing');
            })
            ->where('grading_status', 'ai_graded')
            ->count();
            
        // 3.5. Speaking Reviews needing attention
        $pendingSpeaking = AttemptAnswer::whereHas('question', function ($q) {
                $q->where('skill', 'speaking');
            })
            ->where('grading_status', 'pending')
            ->count();
            
        // 4. Recent completed mock tests (Last 5)
        $recentMockTests = MockTest::with('user')
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
            
        // 5. Expiring users warning (Next 7 days)
        $expiringUsers = User::whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->orderBy('expires_at', 'asc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'mockTestStats',
            'pendingWritings',
            'pendingSpeaking',
            'aiGradedWritings',
            'recentMockTests',
            'expiringUsers'
        ));
    }
}
