<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Nguồn gốc tài khoản — cách nó được TẠO RA, và không đổi về sau.
     *
     * ⚠️ Đừng ghi đè `manual` thành `purchase` khi tài khoản đó gia hạn bằng
     * chuyển khoản. "Được tạo ra thế nào" và "đã từng trả tiền chưa" là hai câu
     * hỏi khác nhau; câu thứ hai trả lời bằng bảng `orders`. Nhồi cả hai vào một
     * cột thì vài tháng nữa không còn tách ra được nữa.
     */
    public const SOURCE_PURCHASE = 'purchase';  // tự mua qua PayOS
    public const SOURCE_MANUAL   = 'manual';    // admin tạo tay ở /admin/users
    public const SOURCE_IMPORT   = 'import';    // dữ liệu cũ, có trước khi có cột này

    public const SOURCE_LABELS = [
        self::SOURCE_PURCHASE => 'Thanh toán qua web',
        self::SOURCE_MANUAL   => 'Admin tự thêm',
        self::SOURCE_IMPORT   => 'Dữ liệu cũ',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'source',
        'status',
        'violation_count',
        'last_violation_at',
        'devtools_guard_disabled',
        'must_change_password',
        'ai_reset_version',
        'speaking_ai_reset_version',
        'ai_extra_uses',
        'expires_at',
        'target_level',
        'max_devices',
        'google_email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'expires_at' => 'datetime',
            'last_violation_at' => 'datetime',
            'devtools_guard_disabled' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function loginSessions()
    {
        return $this->hasMany(LoginSession::class);
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function mockTests()
    {
        return $this->hasMany(MockTest::class);
    }

    public function writingReviews()
    {
        return $this->hasMany(WritingReview::class, 'reviewer_id');
    }

    public function writingAiUsages()
    {
        return $this->hasMany(WritingAiUsage::class);
    }

    public function securityFlags()
    {
        return $this->hasMany(SecurityFlag::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function expirationStatus(): string
    {
        if (!$this->expires_at) return 'never';
        if ($this->isExpired()) return 'expired';
        
        $daysRemaining = now()->diffInDays($this->expires_at, false);
        if ($daysRemaining <= 7) return 'warning';
        
        return 'active';
    }

    public function daysUntilExpiration(): ?int
    {
        if (!$this->expires_at) return null;
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * AI Writing Credit Helpers
     */
    public function getRemainingWritingAiCredits(): int|string
    {
        if ($this->isAdmin()) {
            return 'unlimited';
        }

        $resetVersion = $this->ai_reset_version ?? 0;
        $used = $this->writingAiUsages()
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');

        $defaultLimit = (int)(\App\Models\Setting::where('key', 'default_ai_limit')->value('value') ?? 10);
        $totalLimit = $defaultLimit + ($this->ai_extra_uses ?? 0);

        return max(0, $totalLimit - (int)$used);
    }

    /**
     * Học viên được mời vào lớp online qua Google Calendar: còn hạn, không bị khoá.
     *
     * KHÔNG đòi phải khai `google_email`. 96% học viên đăng ký sẵn bằng @gmail.com,
     * nên mặc định dùng luôn `email` tài khoản — bắt gõ lại chỉ tạo rào cản thừa
     * và khiến danh sách mời rỗng. `google_email` chỉ là bản ghi đè cho số ít
     * người vào Meet bằng tài khoản Google khác.
     */
    public function scopeInvitableToClass(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('role', '!=', 'admin')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('name');
    }

    /** Địa chỉ dùng để mời vào lớp: ưu tiên Gmail đã khai, không có thì lấy email tài khoản. */
    public function classInviteEmail(): string
    {
        return $this->google_email ?: $this->email;
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }

    /** Các lớp học viên là thành viên. */
    public function classGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_user')->withPivot('added_at');
    }

    /** Buổi học được mời THÊM với tư cách khách (ngoài lớp của mình). */
    public function classSessionInvites(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ClassSession::class, 'class_session_user')->withTimestamps();
    }

    /**
     * NGUỒN SỰ THẬT DUY NHẤT cho câu hỏi "người này có được vào buổi đó không".
     *
     * Ba nơi gọi: danh sách `/lop-hoc`, cổng `join`, và thẻ lớp trên dashboard.
     * Viết lại luật ở từng nơi là cách chắc chắn nhất để chúng lệch nhau sau vài
     * tháng — và chỗ lệch sẽ là cổng `join`, tức là chỗ trả link Meet ra ngoài.
     *
     * Chỉ xét TƯ CÁCH THÀNH VIÊN + trạng thái tài khoản. Buổi đã mở cửa chưa là
     * câu hỏi độc lập (`ClassSession::isJoinable()`); phải thoả cả hai.
     */
    public function canJoinClassSession(ClassSession $session): bool
    {
        if ($this->isBlocked() || $this->isExpired()) {
            return false;
        }

        // Buổi không gắn lớp = mở cho mọi học viên còn hạn (hành vi Pha 0).
        if ($session->class_group_id === null) {
            return true;
        }

        // Tắt cả lớp thì mọi buổi của lớp đóng theo, kể cả với khách mời riêng.
        if (! $session->classGroup?->is_active) {
            return false;
        }

        return $this->classGroups()->whereKey($session->class_group_id)->exists()
            || $this->classSessionInvites()->whereKey($session->getKey())->exists();
    }

    /**
     * Ảnh phản chiếu của `canJoinClassSession` theo chiều ngược lại: cho một
     * buổi, ai được vào. Dùng cho email nhắc giờ và cho danh sách mời Calendar.
     *
     * ⚠️ Hàm này và `canJoinClassSession` mô tả CÙNG một luật ở hai chiều. Sửa
     * một cái phải sửa cái kia. `ClassPermissionConsistencyTest` khẳng định hai
     * chiều luôn cho cùng kết quả — nếu nó đỏ thì đúng là chúng đã lệch nhau.
     */
    public function scopeForClassSession(\Illuminate\Database\Eloquent\Builder $query, ClassSession $session): \Illuminate\Database\Eloquent\Builder
    {
        $query->invitableToClass();

        if ($session->class_group_id === null) {
            return $query;
        }

        // Lớp đã tắt → không ai được vào. `whereRaw('1 = 0')` thay vì trả về
        // collection rỗng để hàm luôn trả Builder, chỗ gọi còn nối scope tiếp được.
        if (! $session->classGroup?->is_active) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(fn (\Illuminate\Database\Eloquent\Builder $q) => $q
            ->whereHas('classGroups', fn ($g) => $g->whereKey($session->class_group_id))
            ->orWhereHas('classSessionInvites', fn ($s) => $s->whereKey($session->getKey())));
    }

    /**
     * Học viên SẮP THI trong `$soNgay` ngày tới — dùng cho lớp tự gom.
     *
     * ⚠️ Ô "Ngày thi (Exam Date)" ở form tạo user ghi thẳng vào `expires_at`, nên
     * với tài khoản admin nhập tay thì cột đó ĐÚNG là ngày thi. Nhưng với tài
     * khoản mua qua PayOS, `expires_at` là "ngày mua + 14/30 ngày" — không liên
     * quan gì tới lịch thi. Gom cả họ vào là biến người sắp hết hạn gói thành
     * người sắp thi, và không ai nhận ra vì cả hai đều là một ngày trong tương
     * lai gần. Vì vậy chỉ lấy `manual` và `import`.
     *
     * Người đã qua ngày thi rơi ra khỏi scope này ngay hôm sau — đó là cách lớp
     * tự dọn người đã thi xong mà không cần ai nhớ gỡ.
     */
    public function scopeSapThi(\Illuminate\Database\Eloquent\Builder $query, int $soNgay): \Illuminate\Database\Eloquent\Builder
    {
        return $query->invitableToClass()
            ->whereIn('source', [self::SOURCE_MANUAL, self::SOURCE_IMPORT])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->startOfDay(), now()->addDays($soNgay)->endOfDay()]);
    }

    /** Lọc theo nguồn tài khoản ở màn chọn thành viên. Null/rỗng = không lọc. */
    public function scopeOfSource(\Illuminate\Database\Eloquent\Builder $query, ?string $source): \Illuminate\Database\Eloquent\Builder
    {
        return $source ? $query->where('source', $source) : $query;
    }

    /**
     * Bộ lọc dùng chung cho màn `/admin/users` VÀ cho Export Excel.
     *
     * ⚠️ Trước đây hai chỗ này chép logic của nhau và đã lệch: `UsersExport` chỉ
     * hiểu search/role/status, nên lọc "sắp hết hạn" trên màn rồi bấm Export sẽ
     * ra file chứa TOÀN BỘ người dùng — sai âm thầm, không báo lỗi gì. Gom về một
     * hàm để không thể lệch nữa; thêm bộ lọc mới thì cả hai chỗ được luôn.
     */
    public function scopeFilter(\Illuminate\Database\Eloquent\Builder $query, array $f): \Illuminate\Database\Eloquent\Builder
    {
        if (! empty($f['search'])) {
            $tuKhoa = $f['search'];
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$tuKhoa}%")
                ->orWhere('email', 'like', "%{$tuKhoa}%"));
        }

        foreach (['role', 'status', 'source'] as $cot) {
            if (! empty($f[$cot])) {
                $query->where($cot, $f[$cot]);
            }
        }

        if (! empty($f['account_type'])) {
            match ($f['account_type']) {
                'unlimited' => $query->whereNull('expires_at'),
                'limited'   => $query->whereNotNull('expires_at'),
                default     => $query,
            };
        }

        // "Mới thêm trong N ngày" — tính theo `created_at`, tức là NGÀY TẠO TÀI
        // KHOẢN, không phải ngày thanh toán. Tài khoản cũ gia hạn không hiện ở đây.
        $soNgay = match ($f['joined'] ?? '') {
            '7', '14', '30' => (int) $f['joined'],
            'custom'        => (int) ($f['joined_days'] ?? 0),
            default         => 0,
        };

        if ($soNgay > 0) {
            $query->where('created_at', '>=', now()->subDays($soNgay)->startOfDay());
        }

        if (! empty($f['expiration'])) {
            match ($f['expiration']) {
                'expired' => $query->where('expires_at', '<', now()),
                // Quá hạn LÂU — nhóm gần như chắc chắn sẽ không quay lại. Tách
                // riêng vì "đã quá hạn" gộp cả người vừa hết hạn hôm qua, mà
                // người đó thường gia hạn ngay sau khi thi.
                // `expires_at` NULL không lọt vào: NULL < x cho ra NULL, không
                // phải true — đúng ý, người không giới hạn hạn thì không "quá hạn".
                'expired_30' => $query->where('expires_at', '<', now()->subDays(30)),
                'expired_90' => $query->where('expires_at', '<', now()->subDays(90)),
                'warning' => $query->whereBetween('expires_at', [now(), now()->addDays(7)]),
                'active'  => $query->where('expires_at', '>', now()->addDays(7)),
                'never'   => $query->whereNull('expires_at'),
                'custom'  => empty($f['expire_days']) ? $query : $query->whereBetween('expires_at', [
                    now()->startOfDay(),
                    now()->addDays((int) $f['expire_days'])->endOfDay(),
                ]),
                default   => $query,
            };
        }

        return $query;
    }

    public function recordWritingAiUsage(int $part): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $resetVersion = $this->ai_reset_version ?? 0;
        $usage = $this->writingAiUsages()->firstOrCreate([
            'writing_part' => $part,
            'reset_version' => $resetVersion
        ]);

        $usage->increment('usage_count');
    }

    /**
     * AI Speaking Credit Helpers — đếm theo BÀI, không theo phần.
     *
     * ⚠️ Đơn vị đếm là **một bài nộp (attempt)**, dù bài đó có 4 phần ghi âm.
     * Bản đầu trừ lượt cho từng phần nên hạn mức 10 thực ra chỉ được 2 bài rưỡi —
     * không ai đoán ra điều đó từ con số 10. Đừng đổi ngược lại.
     *
     * Hạn mức lấy từ setting **`speaking_ai_limit`** RIÊNG, không dùng chung
     * `default_ai_limit` với Writing: hai kỹ năng có chi phí API và nhu cầu khác
     * nhau, dùng chung thì chỉnh bên này vô tình đổi bên kia.
     *
     * Trả về int chứ không phải int|string như bản Writing: bản Writing trả
     * chuỗi 'unlimited' cho admin, khiến chỗ gọi phải so sánh `$credits > 0`
     * giữa string và int — chạy đúng chỉ nhờ luật ép kiểu của PHP. Ở đây admin
     * nhận PHP_INT_MAX nên mọi so sánh số học đều đúng nghĩa đen.
     */
    public const SPEAKING_AI_LIMIT_MAC_DINH = 10;

    public function speakingAiUsages()
    {
        return $this->hasMany(SpeakingAiUsage::class);
    }

    public function getSpeakingAiLimit(): int
    {
        $limit = \App\Models\Setting::where('key', 'speaking_ai_limit')->value('value');

        return (int) ($limit ?? self::SPEAKING_AI_LIMIT_MAC_DINH) + ($this->ai_extra_uses ?? 0);
    }

    public function getRemainingSpeakingAiCredits(): int
    {
        if ($this->isAdmin()) {
            return PHP_INT_MAX;
        }

        $daDung = (int) $this->speakingAiUsages()
            ->where('reset_version', $this->speaking_ai_reset_version ?? 0)
            ->sum('usage_count');

        return max(0, $this->getSpeakingAiLimit() - $daDung);
    }

    /**
     * Trừ MỘT lượt cho cả bài, gọi bao nhiêu lần cũng chỉ trừ một.
     *
     * `attempt_id` nằm trong unique key nên tính duy nhất được bảo đảm bởi CẤU
     * TRÚC DỮ LIỆU, không phải bởi việc chỗ gọi nhớ kiểm tra trước. Nộp lại cùng
     * một bài, hay job chạy lại, đều không trừ thêm.
     */
    public function recordSpeakingAiUsageForAttempt(int $attemptId): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->speakingAiUsages()->firstOrCreate(
            [
                'attempt_id'    => $attemptId,
                'reset_version' => $this->speaking_ai_reset_version ?? 0,
            ],
            ['usage_count' => 1, 'speaking_part' => null]
        );
    }

    /**
     * Trả lại lượt của cả bài khi AI hỏng hẳn.
     *
     * Lượt bị trừ ngay lúc nộp (trước khi job chạy) để hai bài nộp liên tiếp
     * không cùng tiêu một lượt. Job chết vĩnh viễn thì học viên không nhận được
     * gì — giữ lượt đã trừ là lấy không của họ.
     *
     * ⚠️ Chỉ hoàn khi **không phần nào** trong bài chấm được. Bài 4 phần mà hỏng
     * 1 phần thì học viên vẫn nhận được 3 phần kết quả; hoàn nguyên lượt lúc đó
     * là cho không một lượt. Việc kiểm điều kiện đó nằm ở chỗ gọi (job), vì chỉ
     * nó biết trạng thái từng phần.
     */
    public function refundSpeakingAiUsageForAttempt(int $attemptId): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->speakingAiUsages()
            ->where('attempt_id', $attemptId)
            ->where('reset_version', $this->speaking_ai_reset_version ?? 0)
            ->delete();
    }
}
