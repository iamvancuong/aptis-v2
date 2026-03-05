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
        'ai_reset_version',
        'speaking_ai_reset_version',
        'ai_extra_uses',
        'expires_at',
        'target_level',
        'max_devices',
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

    public function writingReviews()
    {
        return $this->hasMany(WritingReview::class, 'reviewer_id');
    }

    public function writingAiUsages()
    {
        return $this->hasMany(WritingAiUsage::class);
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
