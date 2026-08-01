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

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'violation_count',
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
     * AI Speaking Credit Helpers
     *
     * Đếm riêng khỏi Writing (bảng riêng + `speaking_ai_reset_version` riêng),
     * nhưng dùng chung hạn mức `default_ai_limit` — nghĩa là học viên có N lượt
     * Writing VÀ N lượt Speaking, không phải N lượt gộp.
     *
     * Trả về int chứ không phải int|string như bản Writing: bản Writing trả
     * chuỗi 'unlimited' cho admin, khiến chỗ gọi phải so sánh `$credits > 0`
     * giữa string và int — chạy đúng chỉ nhờ luật ép kiểu của PHP. Ở đây admin
     * nhận PHP_INT_MAX nên mọi so sánh số học đều đúng nghĩa đen.
     */
    public function speakingAiUsages()
    {
        return $this->hasMany(SpeakingAiUsage::class);
    }

    public function getRemainingSpeakingAiCredits(): int
    {
        if ($this->isAdmin()) {
            return PHP_INT_MAX;
        }

        $resetVersion = $this->speaking_ai_reset_version ?? 0;
        $used = (int) $this->speakingAiUsages()
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');

        $defaultLimit = (int) (\App\Models\Setting::where('key', 'default_ai_limit')->value('value') ?? 10);
        $totalLimit = $defaultLimit + ($this->ai_extra_uses ?? 0);

        return max(0, $totalLimit - $used);
    }

    public function recordSpeakingAiUsage(int $part): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $usage = $this->speakingAiUsages()->firstOrCreate([
            'speaking_part' => $part,
            'reset_version' => $this->speaking_ai_reset_version ?? 0,
        ]);

        $usage->increment('usage_count');
    }

    /**
     * Trả lại lượt đã trừ khi AI hỏng hẳn.
     *
     * Lượt bị trừ ngay lúc nộp bài (trước khi job chạy) để hai bài nộp liên tiếp
     * không cùng tiêu một lượt. Nhưng nếu job chết vĩnh viễn thì học viên không
     * nhận được gì — giữ lượt đã trừ là lấy không của họ.
     */
    public function refundSpeakingAiUsage(int $part): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $usage = $this->speakingAiUsages()
            ->where('speaking_part', $part)
            ->where('reset_version', $this->speaking_ai_reset_version ?? 0)
            ->first();

        if ($usage && $usage->usage_count > 0) {
            $usage->decrement('usage_count');
        }
    }
}
