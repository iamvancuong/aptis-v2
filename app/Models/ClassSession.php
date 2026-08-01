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

    public function joins(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassSessionJoin::class);
    }

    /** Thời điểm nút "Vào lớp" bắt đầu bật. Null = không đặt giờ, mở ngay. */
    public function joinOpensAt(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at?->copy()->subMinutes(self::JOIN_EARLY_MINUTES);
    }

    /** Không đặt giờ kết thúc = không bao giờ tự đóng (admin tắt bằng is_active). */
    public function hasEnded(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /** Đang trong khung giờ vào lớp (đã mở cửa, chưa kết thúc). */
    public function isLive(): bool
    {
        return !$this->hasEnded() && !$this->isUpcoming();
    }

    /** Chỉ "sắp diễn ra" khi có đặt giờ bắt đầu và giờ đó còn ở tương lai. */
    public function isUpcoming(): bool
    {
        return $this->joinOpensAt()?->isFuture() ?? false;
    }

    /** Buổi không đặt giờ nào — mở suốt khi còn bật. */
    public function isAlwaysOpen(): bool
    {
        return $this->starts_at === null && $this->ends_at === null;
    }

    /**
     * Điều kiện phía BUỔI HỌC để được vào. Hạn tài khoản kiểm tra riêng ở
     * controller (User::isExpired) — hai điều kiện độc lập, phải thoả cả hai.
     */
    public function isJoinable(): bool
    {
        return $this->is_active && $this->isLive();
    }

    /** Câu mô tả giờ học cho học viên đọc, chịu được mọi tổ hợp null. */
    public function timeLabel(): string
    {
        if ($this->isAlwaysOpen()) {
            return 'Mở tự do — vào lúc nào cũng được';
        }

        if ($this->starts_at && $this->ends_at) {
            return $this->starts_at->format('H:i') . '–' . $this->ends_at->format('H:i')
                . ', ' . $this->starts_at->format('d/m/Y');
        }

        return $this->starts_at
            ? 'Từ ' . $this->starts_at->format('H:i d/m/Y')
            : 'Đến ' . $this->ends_at->format('H:i d/m/Y');
    }

    public function statusLabel(): string
    {
        if (!$this->is_active)     return 'Đã tắt';
        if ($this->hasEnded())     return 'Đã kết thúc';
        if ($this->isUpcoming())   return 'Sắp diễn ra';
        if ($this->isAlwaysOpen()) return 'Đang mở';

        return 'Đang diễn ra';
    }

    /**
     * Buổi học viên còn thấy được: đang bật và chưa kết thúc.
     * `ends_at` null = không có hạn đóng nên luôn còn hiện.
     */
    public function scopeVisibleToStudents(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            // Buổi có giờ xếp theo giờ; buổi mở tự do (starts_at null) đứng trước.
            ->orderByRaw('starts_at IS NULL DESC')
            ->orderBy('starts_at');
    }
}
