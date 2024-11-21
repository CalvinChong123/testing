<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class FileOrElse implements Rule
{
    public $errorMessages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $fileRules, array $elseRules)
    {
        $this->fileRules = $fileRules;
        $this->elseRules = $elseRules;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $rules = $this->elseRules;
        if ($value instanceof UploadedFile) {
            $rules = $this->fileRules;
        }

        $validator = Validator::make(['attribute' => $value], ['attribute' => $rules], [], ['attribute' => $attribute]);

        if ($validator->fails()) {
            $this->errorMessages = $validator->errors()->toArray();

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessages;
    }
}
