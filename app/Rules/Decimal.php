<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Decimal implements Rule
{
    protected $maxDigits;

    protected $maxDecimalPlaces;

    /**
     * Create a new rule instance.
     *
     * @param  int  $maxDigits
     * @param  int  $maxDecimalPlaces
     * @return void
     */
    public function __construct($maxDigits, $maxDecimalPlaces)
    {
        $this->maxDigits = $maxDigits;
        $this->maxDecimalPlaces = $maxDecimalPlaces;
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
        if (! is_numeric($value)) {
            return false;
        }

        $value = strval($value);
        if (strlen($value) > $this->maxDigits) {
            return false;
        }

        $decimalPos = strpos($value, '.');
        if ($decimalPos !== false && strlen($value) - $decimalPos - 1 > $this->maxDecimalPlaces) {
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
        return "The :attribute must be a decimal number with a maximum of {$this->maxDigits} digits and {$this->maxDecimalPlaces} decimal places.";
    }
}
