<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\LoginSession;
use App\Exports\UsersExport;
use App\Exports\UsersTemplateExport;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by account type (unlimited vs limited)
        if ($request->filled('account_type')) {
            if ($request->account_type === 'unlimited') {
                $query->whereNull('expires_at');
            } elseif ($request->account_type === 'limited') {
                $query->whereNotNull('expires_at');
            }
        }
        
        // Filter by expiration status
        if ($request->filled('expiration')) {
            switch ($request->expiration) {
                case 'expired':
                    $query->where('expires_at', '<', now());
                    break;
                case 'warning': // 1-7 days
                    $query->whereBetween('expires_at', [
                        now(),
                        now()->addDays(7)
                    ]);
                    break;
                case 'custom':
                    if ($request->filled('expire_days')) {
                        $days = (int) $request->expire_days;
                        $query->whereBetween('expires_at', [
                            now()->startOfDay(),
                            now()->addDays($days)->endOfDay()
                        ]);
                    }
                    break;
                case 'active': // > 7 days
                    $query->where('expires_at', '>', now()->addDays(7));
                    break;
                case 'never':
                    $query->whereNull('expires_at');
                    break;
            }
        }

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage)
                      ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['loginSessions' => function($query) {
            $query->orderBy('last_active_at', 'desc');
        }, 'attempts' => function($query) {
            $query->with('quiz')->orderBy('created_at', 'desc');
        }]);

        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 'active';
        $data['violation_count'] = 0;
        
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        // Assign Default Max Devices
        if (!isset($data['max_devices'])) {
            $data['max_devices'] = (int)(\App\Models\Setting::where('key', 'default_max_devices')->value('value') ?? 2);
        }

        $user = User::create($data);
        
        $message = 'User created successfully.';
        if ($request->role === 'user' && !$request->filled('password')) {
            $message .= ' Default password: 12345678';
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        
        // Remove password if empty, hash if provided
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }
        
        $user->update($data);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting admins
        if ($user->isAdmin()) {
            return redirect()->back()
                ->with('error', 'Cannot delete admin users.');
        }
        
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'Cannot delete your own account.');
        }
        
        DB::transaction(function () use ($user) {
            // Get all attempt IDs for this user
            $attemptIds = \App\Models\Attempt::where('user_id', $user->id)->pluck('id');
            
            // Get all attempt answer IDs for these attempts
            $answerIds = \App\Models\AttemptAnswer::whereIn('attempt_id', $attemptIds)->pluck('id');

            // 1. Delete writing reviews (specifically for student's submissions)
            if ($answerIds->isNotEmpty()) {
                \App\Models\WritingReview::whereIn('attempt_answer_id', $answerIds)->delete();
            }

            // 2. Delete attempt answers
            if ($attemptIds->isNotEmpty()) {
                \App\Models\AttemptAnswer::whereIn('attempt_id', $attemptIds)->delete();
            }

            // 3. Delete attempts
            if ($attemptIds->isNotEmpty()) {
                \App\Models\Attempt::whereIn('id', $attemptIds)->delete();
            }

            // 4. Delete mock tests
            \App\Models\MockTest::where('user_id', $user->id)->delete();

            // 5. Delete login sessions
            \App\Models\LoginSession::where('user_id', $user->id)->delete();

            // 6. Delete AI usage history
            \App\Models\WritingAiUsage::where('user_id', $user->id)->delete();

            // 7. Delete the user record
            $user->delete();
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User and all related history deleted successfully.');
    }

    public function extendExpiration(Request $request, User $user)
    {
        $request->validate([
            'days' => 'required|integer|in:30,90,180,365'
        ]);
        
        $currentExpiration = $user->expires_at ?? now();
        $newExpiration = $currentExpiration->addDays($request->days);
        
        $user->update(['expires_at' => $newExpiration]);
        
        return redirect()->back()
            ->with('success', "Expiration extended by {$request->days} days. New expiration: {$newExpiration->format('M d, Y')}");
    }

    public function block(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->back()->with('error', 'Cannot block admin users.');
        }

        $user->update(['status' => 'blocked']);
        
        // Logout user by deleting all login sessions
        LoginSession::where('user_id', $user->id)->delete();

        return redirect()->back()->with('success', 'User has been blocked successfully.');
    }

    public function unblock(User $user)
    {
        $user->update(['status' => 'active']);

        return redirect()->back()->with('success', 'User has been unblocked successfully.');
    }

    public function resetViolations(User $user)
    {
        $user->update(['violation_count' => 0]);

        return redirect()->back()->with('success', 'Violations have been reset successfully.');
    }

    public function resetAi(User $user)
    {
        $user->update([
            'ai_reset_version' => ($user->ai_reset_version ?? 0) + 1,
            'ai_extra_uses' => 0
        ]);

        return redirect()->back()->with('success', 'Writing AI Usage limit has been reset to default.');
    }

    public function resetSpeakingAi(User $user)
    {
        $user->update([
            'speaking_ai_reset_version' => ($user->speaking_ai_reset_version ?? 0) + 1,
            'ai_extra_uses' => 0
        ]);

        return redirect()->back()->with('success', 'Speaking AI Usage limit has been reset to default.');
    }

    public function resetAllAi(User $user)
    {
        $user->update([
            'ai_reset_version' => ($user->ai_reset_version ?? 0) + 1,
            'speaking_ai_reset_version' => ($user->speaking_ai_reset_version ?? 0) + 1,
            'ai_extra_uses' => 0
        ]);

        return redirect()->back()->with('success', 'All AI Usage limits have been reset to default.');
    }

    public function addAi(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:1000'
        ]);

        $user->increment('ai_extra_uses', $request->amount);

        return redirect()->back()->with('success', "Thêm {$request->amount} lượt dùng AI thành công.");
    }

    public function export(Request $request)
    {
        return Excel::download(new UsersExport($request->all()), 'users_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new UsersImport, $request->file('file'));
            return redirect()->route('admin.users.index')
                ->with('success', 'Users imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new UsersTemplateExport, 'users_import_template.xlsx');
    }
}
