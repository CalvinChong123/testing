<?php

namespace App\Http\Requests\Admin\PromotionCreditApprovalReport;


use Illuminate\Foundation\Http\FormRequest;
use App\Models\PromotionCreditApprovalReport;

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
            'promotion_credit_gains' => ['required', 'numeric'],
            'remark' => ['required', 'string'],

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
