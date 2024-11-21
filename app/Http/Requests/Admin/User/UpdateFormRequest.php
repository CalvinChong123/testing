<?php

namespace App\Http\Requests\Admin\User;

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
            // 'name' => ['required', 'string'],
            'id' => ['required', 'integer'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email,' . $this->id],
            'dob' => ['required', 'date'],
            'mobile_number' => ['required', 'string'],
            'ic' => ['required', 'string', 'unique:users,ic,' . $this->id],
            'member_category' => ['nullable', 'string'],
            'member_tier' => ['nullable', 'integer'],
            'referrer' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
}
