<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'meal_id' => $this->meal_id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'description' => $this->description,
            'category' => $this->category,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'is_available' => $this->is_available, // الـ accessor
            'avg_rating' => (float) $this->avg_rating,
            //'ingredients' => IngredientResources::collection($this->whenLoaded('ingredients')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
