<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityFlag extends Model
{
    /**
     * Loại cảnh báo. Hai loại này KHÁC nhau về mức độ đáng ngờ và về việc phải
     * làm gì, nên màn admin tách riêng — gộp chung thì DevTools (hiếm, đáng chú
     * ý) sẽ chìm trong vi phạm thiết bị (nhiều hơn hẳn).
     */
    public const TYPE_DEVTOOLS = 'devtools';
    public const TYPE_DEVICE   = 'device_limit';

    protected $fillable = [
        'user_id',
        'type',
        'ip_address',
        'user_agent',
        'url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
