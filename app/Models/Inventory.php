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

    // العلاقات
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function mealIngredients()
    {
        return $this->hasMany(MealIngredient::class);
    }

    // التحقق من توفر الكمية
    public function hasSufficientQuantity(float $needed): bool
    {
        return $this->quantity >= $needed;
    }

    // خصم الكمية
    public function deduct(float $quantity): void
    {
        if (!$this->hasSufficientQuantity($quantity)) {
            throw new \Exception("Insufficient quantity for ingredient: {$this->ingredient->name}");
        }

        $this->quantity -= $quantity;
        $this->save();
    }

    // إضافة كمية
    public function add(float $quantity): void
    {
        $this->quantity += $quantity;
        $this->save();
    }

    // التحقق من أن الكمية منخفضة
    public function isLow(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }
}
