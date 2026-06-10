<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email address is required',
            'email.email'       => 'Please enter a valid email address',
            'email.exists'      => 'This email is not registered in our system',
            'password.required' => 'Password is required',
            'password.min'      => 'Password must be at least 6 characters',
        ];
    }
}
