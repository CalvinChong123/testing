<?php

namespace App\Http\Requests\Admin\EntityStatus;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EntityStatus;

class CreateFormRequest extends FormRequest
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
            'modelable' => ['required', 'string'],
            'status' => ['required', 'string', 'in:' . implode(',', EntityStatus::STATUS)],
            'remark' => ['nullable', 'string'],
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
