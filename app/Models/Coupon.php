<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_per_user',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    public function isValid(int $userId, float $orderAmount = 0): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->toDateString();
        if ($now < $this->start_date || $now > $this->end_date) {
            return false;
        }

        if ($orderAmount < $this->min_order_amount) {
            return false;
        }

        if ($this->usage_limit) {
            $totalUsed = $this->userCoupons()->whereNotNull('used_at')->count();
            if ($totalUsed >= $this->usage_limit) {
                return false;
            }
        }

        $userUsage = $this->userCoupons()
            ->where('user_id', $userId)
            ->whereNotNull('used_at')
            ->count();

        if ($userUsage >= $this->usage_per_user) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->type === 'percentage') {
            $discount = $orderAmount * ($this->value / 100);

            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }

            return $discount;
        }

        return min($this->value, $orderAmount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now()->toDateString();
        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('is_active', true);
    }
}
