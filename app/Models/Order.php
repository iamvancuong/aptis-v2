<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_GRADING      = 'grading';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED  = 'expired';

    protected $fillable = [
        'order_code',
        'email',
        'type',
        'package',
        'quantity',
        'amount',
        'status',
        'user_id',
        'sale_code',
        'payos_link_id',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'meta'     => 'array',
        'paid_at'  => 'datetime',
        'amount'   => 'integer',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Sinh order_code dạng số, duy nhất — đúng chuẩn `orderCode` của PayOS.
     * Dùng chung cho cả đơn đăng ký và đơn chấm bài.
     */
    public static function generateCode(): int
    {
        do {
            $code = (int) (now()->format('ymdHis') . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT));
        } while (static::where('order_code', $code)->exists());

        return $code;
    }

    /**
     * Số ngày hạn đơn này cấp cho tài khoản (chỉ áp dụng đơn đăng ký).
     */
    public function durationDays(): int
    {
        $package = config("pricing.packages.{$this->package}");

        return $package ? ($package['days'] * $this->quantity) : 0;
    }
}
