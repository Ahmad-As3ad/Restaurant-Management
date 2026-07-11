<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() &&
               in_array(auth('sanctum')->user()->role, ['admin', 'chef']);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:meals|max:100',
            'price' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|max:50',
            'image_url' => 'nullable|url',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'ingredients.*.is_default' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الوجبة مطلوب',
            'name.unique' => 'هذا الاسم موجود بالفعل',
            'price.required' => 'السعر مطلوب',
            'category.required' => 'التصنيف مطلوب',
        ];
    }
}
