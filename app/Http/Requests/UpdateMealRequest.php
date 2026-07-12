<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() &&
               in_array(auth('sanctum')->user()->role, ['admin', 'chef']);
    }

    public function rules(): array
    {
        return [
            'name' => 'string|unique:meals,name,' . $this->meal->meal_id . ',meal_id|max:100',
            'price' => 'numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'category' => 'string|max:50',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'ingredients.*.is_default' => 'boolean'
        ];
    }
}
