<?php

namespace App\Http\Requests\Admin\Approval\ApprovalLayer;

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
            'layers' => ['required', 'array'],
            'layers.*.layer' => ['required', 'integer'],
            'layers.*.role_id' => ['required', 'integer'],
        ];
    }

    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        return [
            'layers.*.role_id.required' => 'Role is required.',
            'layers.*.role_id.exists' => 'The selected role is selected at other layers.',
        ];
    }
}
