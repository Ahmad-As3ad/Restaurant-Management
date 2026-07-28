<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category',
        'image',
        'is_active',
        'rating_avg',
        'rating_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'rating_avg' => 'decimal:2',
    ];

    public function mealIngredients()
    {
        return $this->hasMany(MealIngredient::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'meal_ingredients')
            ->withPivot('quantity', 'is_default')
            ->withTimestamps();
    }

    public function orderMeals()
    {
        return $this->hasMany(OrderMeal::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function getDefaultIngredients()
    {
        return $this->mealIngredients()->where('is_default', true)->get();
    }

    public function getOptionalIngredients()
    {
        return $this->mealIngredients()->where('is_default', false)->get();
    }

    public function updateRating()
    {
        $avg = $this->ratings()->avg('rating') ?? 0;
        $count = $this->ratings()->count();

        $this->update([
            'rating_avg' => round($avg, 2),
            'rating_count' => $count,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
