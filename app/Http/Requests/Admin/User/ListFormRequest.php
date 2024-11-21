<?php

namespace App\Http\Requests\Admin\User;

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
            'role_id' => ['nullable', 'integer'],
            'date_start' => ['sometimes', 'date'],
            'date_end' => ['sometimes', 'date'],
            'page' => ['sometimes', 'integer'],
            'items_per_page' => ['sometimes', 'integer'],
            'filter' => ['sometimes', 'string'],
            // 'sort_by' => ['sometimes', 'string'],
            // 'sort_desc' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'nullable', 'string'],
            'this_outlet_only' => ['sometimes'],

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
