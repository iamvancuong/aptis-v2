<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $attempts = \App\Models\Attempt::where('user_id', $user->id)
            ->whereNotNull('score')
            ->orderBy('finished_at', 'asc')
            ->get();

        $groupedStats = [
            'reading' => [],
            'listening' => [],
            'writing' => [],
            'mock_test' => [],
        ];

        foreach ($attempts as $attempt) {
            if (!$attempt->finished_at) continue;
            
            $dateLabel = $attempt->finished_at->format('d/m');
            $score = (float) $attempt->score;
            
            $partsProgress = [];
            $metadata = $attempt->metadata ?? [];
            if (!empty($metadata['part_stats'])) {
                foreach ($metadata['part_stats'] as $part => $stats) {
                    $total = $stats['total'] ?? 0;
                    $correct = $stats['correct'] ?? 0;
                    $partsProgress[$part] = $total > 0 ? round(($correct / $total) * 100) : 0;
                }
            }

            $skillKey = $attempt->mode === 'mock_test' ? 'mock_test' : $attempt->skill;
            
            if (isset($groupedStats[$skillKey])) {
                if (!isset($groupedStats[$skillKey][$dateLabel])) {
                    $groupedStats[$skillKey][$dateLabel] = [
                        'count' => 0,
                        'total_score' => 0,
                        'parts' => [] // part => [count, total_pct]
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

        $statisticsData = [
            'reading' => [],
            'listening' => [],
            'writing' => [],
            'mock_test' => [],
        ];

        // Flatten groups into averages
        foreach ($groupedStats as $skillKey => $dates) {
            foreach ($dates as $dateLabel => $aggregate) {
                $avgScore = round($aggregate['total_score'] / $aggregate['count'], 2);
                $avgParts = [];
                foreach ($aggregate['parts'] as $part => $pData) {
                    $avgParts[$part] = round($pData['total_pct'] / $pData['count']);
                }
                
                $statisticsData[$skillKey][] = [
                    'date' => $dateLabel,
                    'score' => $avgScore,
                    'parts' => $avgParts
                ];
            }
        }

        foreach ($statisticsData as $key => $series) {
            if (count($series) > 20) {
                $statisticsData[$key] = array_slice($series, -20);
            }
        }

        return view('dashboard', compact('statisticsData'));
    }
}
