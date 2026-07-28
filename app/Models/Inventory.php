<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'ingredient_id',
        'quantity',
        'min_quantity',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_quantity' => 'decimal:2',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function mealIngredients()
    {
        return $this->hasMany(MealIngredient::class);
    }

    public function hasSufficientQuantity(float $needed): bool
    {
        return $this->quantity >= $needed;
    }

    public function deduct(float $quantity): void
    {
        if (!$this->hasSufficientQuantity($quantity)) {
            throw new \Exception("Insufficient quantity for ingredient: {$this->ingredient->name}");
        }

        $this->quantity -= $quantity;
        $this->save();
    }

    public function add(float $quantity): void
    {
        $this->quantity += $quantity;
        $this->save();
    }

    public function isLow(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }
}
