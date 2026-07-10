<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:1000000',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Deposit amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Minimum deposit amount is 0.01',
            'amount.max' => 'Maximum deposit amount is 1,000,000',
        ];
    }
}
