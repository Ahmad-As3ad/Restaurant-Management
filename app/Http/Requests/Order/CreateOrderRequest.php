<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.meal_id' => 'required|exists:meals,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.customizations' => 'nullable|array',
            'items.*.customizations.added' => 'nullable|array',
            'items.*.customizations.added.*' => 'exists:ingredients,id',
            'items.*.customizations.removed' => 'nullable|array',
            'items.*.customizations.removed.*' => 'exists:ingredients,id',
            'pickup_time' => 'required|date|after:now',
            'coupon_code' => 'nullable|string|exists:coupons,code',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.meal_id.required' => 'Meal ID is required',
            'items.*.meal_id.exists' => 'Invalid meal',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'pickup_time.required' => 'Pickup time is required',
            'pickup_time.after' => 'Pickup time must be in the future',
        ];
    }
}
