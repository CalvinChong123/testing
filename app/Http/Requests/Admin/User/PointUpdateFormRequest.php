<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Pylon\FormRequests\Traits\MergeRequestParams;
use App\Models\UserPointBalance;

class PointUpdateFormRequest extends FormRequest
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
