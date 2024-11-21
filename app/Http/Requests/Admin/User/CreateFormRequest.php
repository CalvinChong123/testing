<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

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
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'mobile_number' => ['required', 'regex:/^0\d{9,14}$/', 'unique:users,phone_no'],
            'ic' => ['required', 'string', 'size:12', 'unique:users,ic', function ($attribute, $value, $fail) {
                if (!User::isValidIc($value)) {
                    $fail('Incorrect Ic Format');
                }
            }],
            'member_category' => ['nullable', 'string', 'required_with:member_tier'],
            'member_tier' => ['nullable', 'integer', 'required_with:member_category'],
            'referrer' => ['nullable', 'integer'],
            'dob' => ['required', 'date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
}
