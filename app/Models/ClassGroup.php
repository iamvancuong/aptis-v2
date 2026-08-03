<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lớp học = một nhóm học viên cố định, học nhiều buổi, dùng chung một phòng Meet.
 *
 * Thành viên là DANH SÁCH TƯỜNG MINH (pivot `class_group_user`), không phải kết
 * quả của một bộ lọc. `source_filter` chỉ là gợi ý cho màn chọn thành viên —
 * xem chú thích trong migration `create_class_groups_table`.
 */
class ClassGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'source_filter',
        'meet_link',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_group_user')
            ->withPivot('added_at')
            ->orderBy('name');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Địa chỉ để dán vào ô Khách mời của sự kiện Google Calendar cho lớp này.
     *
     * Lọc bỏ địa chỉ gõ nhầm tên miền: chúng KHÔNG tồn tại, mời vào là mời hư
     * không, mà người đó buổi nào cũng phải xin duyệt và không ai hiểu vì sao.
     * Cùng luật với màn `/admin/class-sessions` (§23).
     */
    public function inviteEmails(): array
    {
        return $this->members
            ->filter(fn (User $u) => ! $u->isExpired() && ! $u->isBlocked())
            ->map->classInviteEmail()
            ->filter(fn (string $e) => \App\Support\InviteEmail::isUsable($e))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Thành viên đã hết hạn nhưng vẫn còn trong lời mời Calendar của lớp.
     *
     * Đây là lỗ hổng thật (§25②): người hết hạn còn lời mời thì mở lịch là có
     * link, vào thẳng Meet mà KHÔNG đi qua cổng web — hệ thống không chặn được.
     * Google không tự gỡ. Phải gỡ tay cho tới khi có `classes:sync-invites`.
     */
    public function membersToRemoveFromInvite(): \Illuminate\Support\Collection
    {
        return $this->members->filter(fn (User $u) => $u->isExpired() || $u->isBlocked());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
