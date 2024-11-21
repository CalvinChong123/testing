<?php

namespace App\Http\Requests\Admin\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Pylon\FormRequests\Traits\MergeRequestParams;

class CurrentMerchantUserFormRequest extends FormRequest
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
            'merchant_id' => ['required', 'integer'],
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
