<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:tables,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'number_of_people' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required' => 'Table is required',
            'table_id.exists' => 'Invalid table',
            'booking_date.required' => 'Booking date is required',
            'booking_date.after_or_equal' => 'Booking date must be today or future',
            'booking_time.required' => 'Booking time is required',
            'booking_time.date_format' => 'Invalid time format (HH:MM)',
            'number_of_people.required' => 'Number of people is required',
            'number_of_people.min' => 'At least 1 person is required',
        ];
    }
}
