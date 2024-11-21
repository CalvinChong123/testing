<?php

namespace App\Http\Requests\Admin\Merchant;

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
            'name' => ['required', 'max:100', new UniqueExcludingSoftDeletes('merchants', 'name', $this->id)],
            'merchant_group' => ['required', 'integer', 'max:100'],
            'image' => ['required', new FileOrElse(['max:10240', 'mimes:jpeg,jpg,png,webp'], [])],
            'asset_no' => ['required', 'integer', 'unique:merchants,asset_no,' . $this->id],
            // 'cid0' => ['required', 'string', 'max:100'],
            // 'cid1' => ['required', 'string', 'max:100'],
            // 'cid2' => ['required', 'string', 'max:100'],
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
