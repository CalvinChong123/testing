<?php

namespace App\Http\Requests\Admin\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Transaction;

class WithdrawalFormRequest extends FormRequest
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
            'withdrawal_method' => ['nullable', 'string', 'in:' . implode(',', Transaction::WITHDRAWAL_METHODS)],
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
