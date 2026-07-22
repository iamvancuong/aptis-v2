<?php

namespace App\Http\Controllers;

use App\Models\SecurityFlag;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Records that the DevTools detector fired for the signed-in learner and
     * ends their session.
     *
     * Deliberately does NOT ban. Detection is a heuristic — window size, a
     * browser extension or a slow device can trip it — so a permanent, machine
     * decided ban would eventually lock out a real, paying learner. Instead this
     * logs the event for an admin to review and act on by hand.
     *
     * Admins are exempt: they use DevTools legitimately.
     */
    public function flagDevtools(Request $request)
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin()) {
            SecurityFlag::create([
                'user_id'    => $user->id,
                'type'       => 'devtools',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url'        => $request->headers->get('referer'),
            ]);

            $user->increment('violation_count');

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['status' => 'flagged']);
    }
}
