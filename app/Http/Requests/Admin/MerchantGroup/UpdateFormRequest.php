<?php

namespace App\Http\Requests\Admin\MerchantGroup;

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
            'id' => ['required', 'integer'],
            'name' => ['required', 'max:100', 'unique:merchant_groups,name,' . $this->id],
            'spending_credits' => ['required', 'integer', 'min:1'],
            'earning_points' => ['required', 'integer', 'min:1'],
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
