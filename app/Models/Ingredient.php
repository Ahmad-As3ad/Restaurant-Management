<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
    ];

    public function mealIngredients()
    {
        return $this->hasMany(MealIngredient::class);
    }

    public function meals()
    {
        return $this->belongsToMany(Meal::class, 'meal_ingredients')
            ->withPivot('quantity', 'is_default')
            ->withTimestamps();
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function orderMealIngredients()
    {
        return $this->hasMany(OrderMealIngredient::class);
    }
}
