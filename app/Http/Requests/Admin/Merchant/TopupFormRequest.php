<?php

namespace App\Http\Requests\Admin\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Transaction;

class TopupFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => ['required', 'integer'],
            'merchant_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', Transaction::PAYMENT_METHODS)],
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'payment_method.in' => 'Payment method must be one of the following: ' . implode(', ', \App\Models\Transaction::PAYMENT_METHODS),
            'type.in' => 'Type must be one of the following: ' . implode(', ', \App\Models\Transaction::TYPE),
            'user_id.required' => 'User is required',
            'merchant_id.required' => 'Merchant is required',
        ];
    }
}
