<?php

namespace App\Http\Requests\Admin\MerchantGroup;

use Illuminate\Foundation\Http\FormRequest;
use Pylon\FormRequests\Traits\MergeRequestParams;

class MerchantListFormRequest extends FormRequest
{
    use MergeRequestParams;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'id' => ['required', 'integer'],
            'date_start' => ['sometimes', 'date'],
            'date_end' => ['sometimes', 'date'],
            'page' => ['sometimes', 'integer'],
            'items_per_page' => ['sometimes', 'integer'],
            'filter' => ['sometimes', 'string'],
            // 'sort_by' => ['sometimes', 'string'],
            // 'sort_desc' => ['sometimes', 'boolean'],
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
