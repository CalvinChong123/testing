<?php

namespace App\Http\Requests\Admin\CashFloat;

use Illuminate\Foundation\Http\FormRequest;

class CashReplenishmentFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cash_float_id' => ['required', 'integer'],
            'remark' => ['nullable'],
            'amount' => ['required', 'numeric'],
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
