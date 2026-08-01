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
     * Học viên đủ điều kiện được mời vào lớp online qua Google Calendar:
     * còn hạn, không bị khoá, và đã khai Gmail dùng để vào lớp.
     *
     * Đây là chỗ DUY NHẤT nối danh tính Milaedu với danh tính Google — mời đúng
     * danh sách này thì người ngoài dù có link cũng không vào thẳng được.
     */
    public function scopeInvitableToClass(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('role', '!=', 'admin')
            ->where('status', 'active')
            ->whereNotNull('google_email')
            ->where('google_email', '!=', '')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->orderBy('name');
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
}
