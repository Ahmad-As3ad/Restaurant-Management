<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'meal_id',
        'quantity',
        'unit_price',
        'total_price',
        'customizations',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'customizations' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function orderMealIngredients()
    {
        return $this->hasMany(OrderMealIngredient::class);
    }

    public function calculateTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
