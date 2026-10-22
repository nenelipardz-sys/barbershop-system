<?php
/**
 * ValidationException — carries user-friendly error messages back to the form.
 */
class ValidationException extends Exception
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(implode(' ', $errors));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
