<?php

namespace App\Exceptions;

use Exception;

class BadRequestException extends Exception
{
    protected $errors;

    public function __construct($message = "", $code = 0, $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
