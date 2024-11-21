<?php

namespace App\Http\Requests\Admin\PromotionCreditTier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            '*.id' => ['required', 'integer'],
            '*.name' => ['required', 'string', 'max:100'],
            '*.total_spend' => ['required', 'numeric'],
            '*.credit_earn' => ['required', 'numeric'],
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
