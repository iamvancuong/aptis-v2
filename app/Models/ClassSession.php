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
        'class_group_id',
        'title',
        'description',
        'meet_link',
        'starts_at',
        'ends_at',
        'is_active',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function joins(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClassSessionJoin::class);
    }

    /** Lớp sở hữu buổi này. Null = buổi mở cho mọi học viên còn hạn. */
    public function classGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** Khách được mời THÊM riêng buổi này (học thử, học bù) — ngoài thành viên lớp. */
    public function extraMembers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_session_user')->withTimestamps();
    }

    /**
     * Link phòng thật sự dùng cho buổi này: link riêng của buổi nếu có, không thì
     * kế thừa link của lớp.
     *
     * Mặc định kế thừa để admin chỉ phải dán link MỘT lần cho cả lớp. Đây không
     * chỉ là tiện: bắt dán lại mỗi buổi thì sớm muộn cũng có lần dán nhầm link
     * của lớp khác vào — và đó là rò phòng học, không phải lỗi chính tả.
     */
    public function effectiveMeetLink(): ?string
    {
        return $this->meet_link ?: $this->classGroup?->meet_link;
    }

    public function hasMeetLink(): bool
    {
        return filled($this->effectiveMeetLink());
    }

    /**
     * Lọc theo TƯ CÁCH THÀNH VIÊN — buổi mà $user được phép vào.
     *
     * Chiều ngược lại (cho một buổi, ai được vào) là `User::scopeForClassSession`.
     * Hai chiều phải khớp nhau; xem chú thích ở đó.
     */
    public function scopeAllowedFor(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            // Buổi không thuộc lớp nào → mở cho mọi học viên còn hạn (Pha 0).
            ->whereNull('class_group_id')
            // Hoặc: lớp ĐANG BẬT và user là thành viên lớp / khách mời của buổi.
            ->orWhere(fn (Builder $q2) => $q2
                ->whereHas('classGroup', fn ($g) => $g->where('is_active', true))
                ->where(fn (Builder $q3) => $q3
                    ->whereHas('classGroup', fn ($g) => $g->whereHas(
                        'members', fn ($m) => $m->whereKey($user->getKey())
                    ))
                    ->orWhereHas('extraMembers', fn ($m) => $m->whereKey($user->getKey())))));
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
        // Không có link (buổi chưa dán link mà lớp cũng chưa có) thì nút "Vào lớp"
        // chỉ dẫn tới một trang lỗi. Coi như chưa mở cửa, đừng hiện nút.
        return $this->is_active && $this->isLive() && $this->hasMeetLink();
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
