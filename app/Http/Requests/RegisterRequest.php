<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name'  => 'required|string|max:50',
            'phone'      => 'required|string|size:10|regex:/^09[0-9]{8}$/|unique:users,phone',
            'address'    => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.string'   => 'First name must be a string',
            'first_name.max'      => 'First name must not exceed 50 characters',

            'last_name.required'  => 'Last name is required',
            'last_name.string'    => 'Last name must be a string',
            'last_name.max'       => 'Last name must not exceed 50 characters',

            'phone.required'      => 'Phone number is required',
            'phone.size'          => 'Phone number must be exactly 10 digits',
            'phone.regex'         => 'Phone number must start with 09',
            'phone.unique'        => 'Phone number is already registered',

            'address.required'    => 'Address is required',
            'address.string'      => 'Address must be a string',
            'address.max'         => 'Address must not exceed 255 characters',

            'email.required'      => 'Email address is required',
            'email.email'         => 'Please enter a valid email address',
            'email.unique'        => 'Email address is already registered',

            'password.required'   => 'Password is required',
            'password.min'        => 'Password must be at least 6 characters',
            'password.confirmed'  => 'Password confirmation does not match',
        ];
    }
}
