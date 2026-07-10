<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total',
        'pickup_time',
        'ready_at',
        'collected_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'pickup_time' => 'datetime',
        'ready_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderMeals()
    {
        return $this->hasMany(OrderMeal::class);
    }

    public function walletTransactions()
    {
        return $this->morphMany(WalletTransaction::class, 'reference');
    }

    // الحالات المسموح فيها الإلغاء
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'preparing']);
    }

    // التحقق من الإلغاء
    public function isCancellable(): bool
    {
        return $this->canBeCancelled();
    }

    // دوال الحالة
    public function markAsPreparing(): void
    {
        if ($this->status === 'pending') {
            $this->status = 'preparing';
            $this->save();
        }
    }

    public function markAsReady(): void
    {
        if ($this->status === 'preparing') {
            $this->status = 'ready';
            $this->ready_at = now();
            $this->save();
        }
    }

    public function markAsCollected(): void
    {
        if ($this->status === 'ready') {
            $this->status = 'collected';
            $this->collected_at = now();
            $this->save();
        }
    }

    public function markAsCancelled(): void
    {
        if ($this->canBeCancelled()) {
            $this->status = 'cancelled';
            $this->save();
        }
    }
}
