<?php

namespace App\Http\Requests\Admin\CashFloat;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutFormRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['required', 'integer'],
            'cash_out' => ['required', 'numeric'],
            'remark' => ['nullable', 'string'],
            'end_time' => ['required', 'date'],
            'end_shift_today' => ['required', 'boolean'],
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
