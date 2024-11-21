<?php

namespace App\Http\Requests\Admin\ReferenceData;

use Illuminate\Foundation\Http\FormRequest;

class GetInfoBundleFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'role_id' => ['nullable', 'integer'],
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
