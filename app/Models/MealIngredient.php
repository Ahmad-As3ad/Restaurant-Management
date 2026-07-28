<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealIngredient extends Model
{
    use HasFactory;

    protected $table = 'meal_ingredients';

    protected $fillable = [
        'meal_id',
        'ingredient_id',
        'quantity',
        'is_default',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
