<?php

namespace App\Http\Requests\Admin\PublicFile;

use App\Models\PublicFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadFileRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'path_type' => $this->route('path_type'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'path_type' => ['required', Rule::in($this->getAllowedTypes())],
            'upload' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,webp'],
        ];
    }

    private function getAllowedTypes(): array
    {
        return [
            PublicFile::MODULE_PATH_CKEDITOR,
        ];
    }
}
