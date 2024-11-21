<?php

namespace App\Http\Requests\Admin\ExportHub;

use Illuminate\Foundation\Http\FormRequest;

class ListFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => ['sometimes', 'integer'],
            'items_per_page' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'nullable', 'string'],
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
