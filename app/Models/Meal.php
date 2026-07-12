<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meal extends Model
{
    use HasFactory;

    protected $table = 'meals';
    protected $primaryKey = 'meal_id';
    protected $keyType = 'int';
    public $incrementing = true;

    protected $fillable = [
        'name',
        'price',
        'description',
        'category',
        'image_url',
        'is_active',
        'avg_rating'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function ingredients()
    {
        return $this->belongsToMany(
            Ingredient::class,
            'meal_ingredients',
            'meal_id',
            'ingredient_id'
        )->withPivot('is_default');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'meal_id', 'meal_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'meal_id', 'meal_id');
    }

    // الـ Accessor
    public function getIsAvailableAttribute()
    {
        // الوجبة غير متوفرة إذا أي مكون افتراضي نفذ
        $hasUnavailableDefault = $this->ingredients()
            ->where('is_default', true)
            ->where('is_available', false)
            ->exists();

        return $this->is_active && !$hasUnavailableDefault;
    }
}
