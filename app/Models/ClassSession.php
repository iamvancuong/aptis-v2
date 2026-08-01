<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Buổi học online (Pha 0 — admin dán link Google Meet thủ công).
 *
 * Nguyên tắc chống "học chui": link Meet KHÔNG bao giờ render ra HTML. Học viên
 * chỉ thấy nút "Vào lớp" trỏ tới route join; ở đó mới kiểm tra hạn tài khoản +
 * khung giờ rồi redirect. Xem nguồn trang không lấy được link để gửi ra ngoài.
 */
class ClassSession extends Model
{
    use HasFactory;

    /** Mở nút "Vào lớp" sớm hơn giờ bắt đầu ngần này phút (cho học viên vào chờ). */
    public const JOIN_EARLY_MINUTES = 15;

    protected $fillable = [
        'title',
        'description',
        'meet_link',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** Thời điểm nút "Vào lớp" bắt đầu bật. */
    public function joinOpensAt(): \Illuminate\Support\Carbon
    {
        return $this->starts_at->copy()->subMinutes(self::JOIN_EARLY_MINUTES);
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /** Đang trong khung giờ vào lớp (đã mở cửa, chưa kết thúc). */
    public function isLive(): bool
    {
        return !$this->hasEnded() && !$this->joinOpensAt()->isFuture();
    }

    public function isUpcoming(): bool
    {
        return $this->joinOpensAt()->isFuture();
    }

    /**
     * Điều kiện phía BUỔI HỌC để được vào. Hạn tài khoản kiểm tra riêng ở
     * controller (User::isExpired) — hai điều kiện độc lập, phải thoả cả hai.
     */
    public function isJoinable(): bool
    {
        return $this->is_active && $this->isLive();
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) return 'Đã tắt';
        if ($this->hasEnded())  return 'Đã kết thúc';
        if ($this->isLive())    return 'Đang diễn ra';

        return 'Sắp diễn ra';
    }

    /** Buổi học viên còn thấy được: đang bật và chưa kết thúc. */
    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at');
    }
}
