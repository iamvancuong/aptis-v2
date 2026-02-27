<?php

namespace App\Http\Controllers;

use App\Models\WritingAiUsage;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // AI limit per part per reset cycle
    const AI_LIMIT_PER_PART = 5;

    public function index()
    {
        $user = auth()->user();

        $attempts = \App\Models\Attempt::where('user_id', $user->id)
            ->whereNotNull('score')
            ->orderBy('finished_at', 'asc')
            ->get();

        $groupedStats = [
            'reading'   => [],
            'listening' => [],
            'writing'   => [],
            'grammar'   => [],
            'mock_test' => [],
        ];

        foreach ($attempts as $attempt) {
            if (!$attempt->finished_at) continue;

            $dateLabel = $attempt->finished_at->format('d/m');
            $score     = (float) $attempt->score;

            $partsProgress = [];
            $metadata      = $attempt->metadata ?? [];
            if (!empty($metadata['part_stats'])) {
                foreach ($metadata['part_stats'] as $part => $stats) {
                    $total   = $stats['total'] ?? 0;
                    $correct = $stats['correct'] ?? 0;
                    $partsProgress[$part] = $total > 0 ? round(($correct / $total) * 100) : 0;
                }
            }

            $skillKey = $attempt->mode === 'mock_test' ? 'mock_test' : $attempt->skill;

            if (isset($groupedStats[$skillKey])) {
                if (!isset($groupedStats[$skillKey][$dateLabel])) {
                    $groupedStats[$skillKey][$dateLabel] = [
                        'count'       => 0,
                        'total_score' => 0,
                        'parts'       => [],
                    ];
                }

                $g = &$groupedStats[$skillKey][$dateLabel];
                $g['count']++;
                $g['total_score'] += $score;

                foreach ($partsProgress as $part => $pct) {
                    if (!isset($g['parts'][$part])) {
                        $g['parts'][$part] = ['count' => 0, 'total_pct' => 0];
                    }
                    $g['parts'][$part]['count']++;
                    $g['parts'][$part]['total_pct'] += $pct;
                }
            }
        }

        $statisticsData = ['reading' => [], 'listening' => [], 'writing' => [], 'grammar' => [], 'mock_test' => []];

        foreach ($groupedStats as $skillKey => $dates) {
            foreach ($dates as $dateLabel => $aggregate) {
                $avgScore = round($aggregate['total_score'] / $aggregate['count'], 2);
                $avgParts = [];
                foreach ($aggregate['parts'] as $part => $pData) {
                    $avgParts[$part] = round($pData['total_pct'] / $pData['count']);
                }

                $statisticsData[$skillKey][] = [
                    'date'  => $dateLabel,
                    'score' => $avgScore,
                    'parts' => $avgParts,
                ];
            }
        }

        foreach ($statisticsData as $key => $series) {
            if (count($series) > 20) {
                $statisticsData[$key] = array_slice($series, -20);
            }
        }

        // --- Quick Stats ---
        $totalAttempts = $attempts->count();
        $avgScore      = $totalAttempts > 0 ? round($attempts->avg('score'), 1) : null;

        // AI usage: check against current reset_version (overall usage)
        $resetVersion = $user->ai_reset_version ?? 0;
        $aiUsagesCount = WritingAiUsage::where('user_id', $user->id)
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');
            
        $totalAiLimit = 10 + ($user->ai_extra_uses ?? 0);
        $aiRemaining  = max(0, $totalAiLimit - $aiUsagesCount);

        if ($user->isAdmin()) {
            $totalAiLimit = -1; // -1 represents unlimited
            $aiRemaining  = -1;
        }

        // Account expiry
        $expiresAt          = $user->expires_at;
        $daysUntilExpiry    = $user->daysUntilExpiration();
        $expirationStatus   = $user->expirationStatus();

        // Writing graded notification: attempts with at least one answer graded since last visit
        $newlyGraded = \App\Models\Attempt::where('user_id', $user->id)
            ->where('skill', 'writing')
            ->whereHas('attemptAnswers', fn($q) => $q->where('grading_status', 'graded'))
            ->whereDate('updated_at', '>=', now()->subDays(7))
            ->count();

        return view('dashboard', compact(
            'statisticsData',
            'totalAttempts',
            'avgScore',
            'aiRemaining',
            'totalAiLimit',
            'expiresAt',
            'daysUntilExpiry',
            'expirationStatus',
            'newlyGraded'
        ));
    }
}
