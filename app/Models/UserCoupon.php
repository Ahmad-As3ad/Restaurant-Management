<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_id',
        'usage_count',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function markAsUsed(int $orderId): void
    {
        $this->usage_count += 1;
        $this->order_id = $orderId;
        $this->used_at = now();
        $this->save();
    }
}
