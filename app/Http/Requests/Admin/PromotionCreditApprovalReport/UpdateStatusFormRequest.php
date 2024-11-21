<?php

namespace App\Http\Requests\Admin\PromotionCreditApprovalReport;


use Illuminate\Foundation\Http\FormRequest;
use App\Models\PromotionCreditApprovalReport;

class UpdateStatusFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ids' => ['required', 'array'],
            'status' => ['required', 'string', 'in:' . implode(',', PromotionCreditApprovalReport::STATUS)],

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
