<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityFlag;
use Illuminate\Http\Request;

class SecurityFlagController extends Controller
{
    /**
     * Review queue for accounts the DevTools detector flagged.
     *
     * These signals are heuristic, so this is a list for a human to judge — the
     * admin decides whether to block, using the existing block action.
     */
    public function index(Request $request)
    {
        $flags = SecurityFlag::with('user')
            ->latest()
            ->paginate(30);

        // Per-user rollup: how many times, when last, current status.
        $summary = SecurityFlag::selectRaw('user_id, count(*) as flag_count, max(created_at) as last_flagged_at')
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('last_flagged_at')
            ->get();

        return view('admin.security-flags.index', compact('flags', 'summary'));
    }
}
