<?php

namespace App\Http\Requests\Admin\UserPointBalance;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\UserPointBalance;

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
            'user_id' => ['required', 'integer'],
            'point' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:' . implode(',', UserPointBalance::TYPE)],
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
