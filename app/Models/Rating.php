<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meal_id',
        'order_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function canRate(int $userId, int $mealId, int $orderId): bool
    {
        $orderMeal = OrderMeal::where('order_id', $orderId)
            ->where('meal_id', $mealId)
            ->exists();

        if (!$orderMeal) {
            return false;
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'collected') {
            return false;
        }

        if ($order->user_id !== $userId) {
            return false;
        }

        $exists = self::where('user_id', $userId)
            ->where('meal_id', $mealId)
            ->where('order_id', $orderId)
            ->exists();

        return !$exists;
    }
}
