<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một lượt học viên bấm "Vào lớp" và được hệ thống cho qua.
 *
 * Ghi lại để (1) nội quy hiển thị cho học viên là lời nói thật, và (2) phát hiện
 * chia sẻ link: cùng một tài khoản vào lớp từ nhiều địa chỉ mạng trong một buổi.
 */
class ClassSessionJoin extends Model
{
    protected $fillable = [
        'user_id',
        'class_session_id',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }
}
