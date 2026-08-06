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

        // Bộ lọc nằm ở `User::scopeFilter` để màn này và Export Excel dùng CHUNG
        // một logic — trước đây hai chỗ chép của nhau và đã lệch (xem chú thích
        // ở scope).
        $query = User::filter($request->all());

        // Count DevTools detections per user so the list can flag repeat offenders.
        $query->withCount(['securityFlags as devtools_flag_count' => function ($q) {
            $q->where('type', 'devtools');
        }]);

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage)
                      ->withQueryString();

        // Đếm nhanh theo nguồn để admin thấy ngay cơ cấu tài khoản, không phải
        // bấm từng bộ lọc mới biết mỗi nhóm có bao nhiêu người.
        $demNguon = User::where('role', '!=', 'admin')
            ->selectRaw('source, COUNT(*) as tong')
            ->groupBy('source')
            ->pluck('tong', 'source');

        // Đếm theo tình trạng hạn, hiện thẳng vào ô lọc "Hạn" — cùng kiểu với
        // `$demNguon`. Mục đích là nhìn phát biết ngay có bao nhiêu tài khoản quá
        // hạn lâu, không phải bấm từng bộ lọc rồi đọc số ở chân bảng.
        //
        // Gộp một truy vấn thay vì 6 lần `count()`. `expires_at` NULL không lọt
        // vào nhánh nào của "quá hạn" vì `NULL < x` cho ra NULL chứ không phải
        // true — khớp đúng với `scopeFilter`, hai chỗ không được lệch nhau.
        $demHan = User::where('role', '!=', 'admin')
            ->selectRaw(
                'SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired,'
                . ' SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired_30,'
                . ' SUM(CASE WHEN expires_at < ? THEN 1 ELSE 0 END) as expired_90,'
                . ' SUM(CASE WHEN expires_at >= ? AND expires_at <= ? THEN 1 ELSE 0 END) as warning,'
                . ' SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) as active,'
                . ' SUM(CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END) as never',
                [
                    now(), now()->subDays(30), now()->subDays(90),
                    now(), now()->addDays(7), now()->addDays(7),
                ]
            )
            ->first();

        return view('admin.users.index', compact('users', 'demNguon', 'demHan'));
    }

    public function show(User $user)
    {
        $user->load(['loginSessions' => function($query) {
            $query->orderBy('last_active_at', 'desc');
        }, 'attempts' => function($query) {
            $query->with(['set.quiz', 'mockTest'])->orderBy('created_at', 'desc');
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
        // Gán ở controller chứ không qua rules(): `validated()` chỉ trả về các key
        // đã khai trong rules, nên để trong form request thì admin sửa được nguồn
        // bằng cách thêm field vào POST. Nguồn tài khoản không phải input.
        $data['source'] = \App\Models\User::SOURCE_MANUAL;
        
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

    /**
     * Gỡ khoá PHẢI reset vi phạm cùng lúc.
     *
     * Bẫy của bản cũ: chỉ đổi `status` về active, `violation_count` giữ nguyên ở
     * mức đã chạm ngưỡng. Tài khoản vừa mở ra là lần đăng nhập thiết bị mới kế
     * tiếp lại khoá ngay — admin bấm Unblock thấy "thành công" rồi học viên vẫn
     * kêu không vào được, không ai hiểu vì sao.
     */
    public function unblock(User $user)
    {
        $user->update([
            'status'            => 'active',
            'violation_count'   => 0,
            'last_violation_at' => null,
        ]);

        return redirect()->back()->with('success', 'Đã mở khoá tài khoản và xoá số lần vi phạm.');
    }

    public function resetViolations(User $user)
    {
        $user->update(['violation_count' => 0]);

        return redirect()->back()->with('success', 'Violations have been reset successfully.');
    }

    /**
     * Exempt this account from the DevTools guard, or put it back under it.
     */
    public function toggleDevtoolsGuard(User $user)
    {
        $user->update(['devtools_guard_disabled' => ! $user->devtools_guard_disabled]);

        $message = $user->devtools_guard_disabled
            ? "Đã TẮT chặn DevTools cho {$user->email}."
            : "Đã BẬT lại chặn DevTools cho {$user->email}.";

        return redirect()->back()->with('success', $message);
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
