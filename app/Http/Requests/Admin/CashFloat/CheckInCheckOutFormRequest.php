<?php

namespace App\Http\Requests\Admin\CashFloat;

use Illuminate\Foundation\Http\FormRequest;

class CheckInCheckOutFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => ['required', 'numeric'],
            'end_shift_today' => ['nullable', 'boolean'],
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
