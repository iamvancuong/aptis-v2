<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakingAiUsage extends Model
{
    /**
     * ⚠️ `attempt_id` PHẢI có ở đây. Đơn vị tính lượt là một BÀI, và `attempt_id`
     * vừa là khoá duy nhất vừa là thứ để hoàn lượt. Thiếu nó thì mass assignment
     * bỏ qua **im lặng**: dòng vẫn được tạo nhưng `attempt_id` = null, nên lượt
     * không trừ đúng và hoàn lượt không tìm thấy dòng nào để xoá.
     */
    protected $fillable = [
        'user_id',
        'attempt_id',
        'speaking_part',
        'usage_count',
        'reset_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }
}
