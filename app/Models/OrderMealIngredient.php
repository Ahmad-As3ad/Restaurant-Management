<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderMealIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_meal_id',
        'ingredient_id',
        'action', // added, removed
    ];

    // العلاقات
    public function orderMeal()
    {
        return $this->belongsTo(OrderMeal::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
