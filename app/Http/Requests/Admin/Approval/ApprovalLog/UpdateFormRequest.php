<?php

namespace App\Http\Requests\Admin\Approval\ApprovalLog;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ApprovalLog;

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
            'status' => ['required', 'string', 'in:' . implode(',', ApprovalLog::STATUS)],
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
