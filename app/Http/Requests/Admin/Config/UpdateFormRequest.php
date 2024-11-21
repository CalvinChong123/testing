<?php

namespace App\Http\Requests\Admin\Config;

use App\Rules\FileOrElse;
use App\Rules\UniqueExcludingSoftDeletes;
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
            'months' => ['nullable', 'integer'],
            'days' => ['nullable', 'integer'],
            'credits' => ['nullable', 'integer'],
            'points' => ['nullable', 'integer'],
            'outlet_name' => ['nullable', 'string'],
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
